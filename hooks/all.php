<?php
// Adds the theme CSS to every page and, when enabled, the light/dark mode switch button.
// Function name format: Hook + <plugin> + <page> + <hook name>
function HookCustomcssAllAdditionalheaderjs()
{
    global $CSP_nonce;
    require_once dirname(__FILE__, 2) . '/include/functions.php';

    $nonce = !empty($CSP_nonce) ? ' nonce="' . htmlspecialchars($CSP_nonce) . '"' : '';
    $base  = $GLOBALS['baseurl_short'] . 'Custom/themes/';
    $s     = customcss_settings();

    // URL of a theme file; ?v= busts the browser cache whenever the file changes
    $url = function ($slug) use ($base) {
        return $base . $slug . '.css?v=' . (int) filemtime(customcss_dir() . '/themes/' . $slug . '.css');
    };

    $links  = array(); // each entry: array(href, media, extra class)
    $switch = false;
    $urls   = array();
    $mode   = 'light';

    if ($s['switch'] && customcss_theme_exists($s['light_theme']) && customcss_theme_exists($s['dark_theme'])) {
        // Light/dark mode switch is on: the user's choice (cookie) wins over the default mode
        $switch = true;
        $urls   = array('light' => $url($s['light_theme']), 'dark' => $url($s['dark_theme']));
        $mode   = isset($_COOKIE['customcss_mode']) ? (string) $_COOKIE['customcss_mode'] : $s['default_mode'];
        if (!in_array($mode, array('auto', 'light', 'dark'), true)) {
            $mode = $s['default_mode'];
        }
        if ($mode === 'auto') {
            // Follow the device setting
            $links[] = array($urls['light'], '(prefers-color-scheme: light)');
            $links[] = array($urls['dark'], '(prefers-color-scheme: dark)');
        } else {
            $links[] = array($urls[$mode], '');
        }
    } else {
        // Single active theme
        $slug = customcss_active();
        if ($slug === '') {
            return false;
        }
        $links[] = array($url($slug), '');
    }

    // Main (shared) theme: loaded first, so the light/dark (or single active) theme can override it.
    // It keeps the class customcss-main, so the switch button does not remove it.
    $main  = $s['main_theme'];
    $theme = $switch ? array($s['light_theme'], $s['dark_theme']) : array(customcss_active());
    if ($main !== '' && customcss_theme_exists($main) && !in_array($main, $theme, true)) {
        array_unshift($links, array($url($main), '', ' customcss-main'));
    }

    foreach ($links as $l) {
        echo '<link class="customcss-theme' . ($l[2] ?? '') . '" rel="stylesheet" href="' . htmlspecialchars($l[0]) . '"'
            . ($l[1] !== '' ? ' media="' . htmlspecialchars($l[1]) . '"' : '') . '>' . "\n";
    }

    // ResourceSpace prints this hook BEFORE global.css and light.css. This script moves the
    // theme link(s) to the end of <head> so the theme loads last and overrides the standard styles.
    $mover = <<<'JS'
(function(){var l=document.querySelectorAll("link.customcss-theme");if(!l.length){return;}
function m(){for(var i=0;i<l.length;i++){document.head.appendChild(l[i]);}}
if(document.body){m();return;}
new MutationObserver(function(r,o){if(document.body){o.disconnect();m();}}).observe(document.documentElement,{childList:true});})();
JS;
    echo '<script' . $nonce . '>' . $mover . '</script>' . "\n";

    if ($switch) {
        // Look of the floating switch button (bottom right; change right/bottom to move it)
        $css = <<<'CSS'
#customcss-switch{all:unset;box-sizing:border-box;position:fixed;right:16px;bottom:84px;z-index:2147483000;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;cursor:pointer;font:20px/1 sans-serif;background:#fff;color:#1f2933;border:1px solid rgba(0,0,0,.25);box-shadow:0 2px 8px rgba(0,0,0,.3)}
#customcss-switch[data-mode=dark]{background:#13203a;color:#ffd54a;border-color:rgba(255,255,255,.25)}
#customcss-switch:hover{filter:brightness(1.1)}
#customcss-switch:focus-visible{outline:2px solid #3b82f6;outline-offset:2px}
CSS;

        // Switch logic: builds the button, swaps the theme link and remembers the choice in a cookie
        $js = <<<'JS'
(function () {
    if (window.top !== window.self) { return; } // not inside iframes (e.g. the preview)
    var urls = __URLS__;
    var mode = __MODE__;
    var btn = null;
    var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function effective() {
        if (mode === 'auto') { return (mq && mq.matches) ? 'dark' : 'light'; }
        return mode;
    }
    function refresh() {
        if (!btn) { return; }
        var dark = effective() === 'dark';
        var label = dark ? 'Switch to light mode' : 'Switch to dark mode';
        btn.setAttribute('data-mode', dark ? 'dark' : 'light');
        btn.setAttribute('title', label);
        btn.setAttribute('aria-label', label);
        btn.textContent = dark ? '\u2600\uFE0E' : '\u263E\uFE0E';
    }
    function apply(next) {
        var old = document.querySelectorAll('link.customcss-theme:not(.customcss-main)');
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.className = 'customcss-theme';
        link.href = urls[next];
        link.onload = function () {
            for (var i = 0; i < old.length; i++) {
                if (old[i].parentNode) { old[i].parentNode.removeChild(old[i]); }
            }
        };
        document.head.appendChild(link);
        mode = next;
        document.cookie = 'customcss_mode=' + next + ';path=/;max-age=31536000;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
        refresh();
    }
    function init() {
        btn = document.createElement('button');
        btn.id = 'customcss-switch';
        btn.type = 'button';
        btn.addEventListener('click', function () { apply(effective() === 'dark' ? 'light' : 'dark'); });
        document.body.appendChild(btn);
        refresh();
    }
    if (mq && mq.addEventListener) { mq.addEventListener('change', refresh); }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
JS;
        $js = str_replace(array('__URLS__', '__MODE__'), array(json_encode($urls), json_encode($mode)), $js);

        echo '<style' . $nonce . '>' . $css . '</style>' . "\n";
        echo '<script' . $nonce . '>' . $js . '</script>' . "\n";
    }

    // Return false so other plugins can still use this hook
    return false;
}
