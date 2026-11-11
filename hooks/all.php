<?php
// Adds the active theme to every page (main.css, extra files, then light-mode.css or dark-mode.css)
// and, when enabled, the light/dark mode switch button.
// Function name format: Hook + <plugin> + <page> + <hook name>
function HookCustomcssAllAdditionalheaderjs()
{
    global $CSP_nonce;
    require_once dirname(__FILE__, 2) . '/include/functions.php';

    $nonce = !empty($CSP_nonce) ? ' nonce="' . htmlspecialchars($CSP_nonce) . '"' : '';

    // Converts the old layout (one CSS file per theme, before 1.13) the first time it is seen
    try {
        customcss_migrate();
    } catch (Throwable $e) {
        // never break a page because of the conversion; the options page shows problems
    }

    $s     = customcss_settings();
    $theme = customcss_active();
    if ($theme === '') {
        return false;
    }
    $dir  = customcss_theme_dir($theme);
    $base = $GLOBALS['baseurl_short'] . 'Custom/themes/' . rawurlencode($theme) . '/';

    // URL of a theme file; ?v= busts the browser cache whenever the file changes
    $url = function ($name) use ($dir, $base) {
        return $base . $name . '.css?v=' . (int) filemtime($dir . '/' . $name . '.css');
    };

    // main.css and the extra files: same in both modes (class customcss-main = kept by the switch)
    $links = array(); // each entry: array(href, media, extra class)
    foreach (customcss_file_names($dir) as $name) {
        if (!in_array($name, array('light-mode', 'dark-mode'), true) && is_file($dir . '/' . $name . '.css')) {
            $links[] = array($url($name), '', ' customcss-main');
        }
    }

    // light-mode.css / dark-mode.css
    $urls = array();
    foreach (array('light' => 'light-mode', 'dark' => 'dark-mode') as $mode_name => $file) {
        if (is_file($dir . '/' . $file . '.css')) {
            $urls[$mode_name] = $url($file);
        }
    }
    $switch = $s['switch'] && count($urls) === 2;
    $mode   = $s['default_mode'];
    if ($switch && isset($_COOKIE['customcss_mode']) && in_array($_COOKIE['customcss_mode'], array('auto', 'light', 'dark'), true)) {
        $mode = (string) $_COOKIE['customcss_mode']; // the user's own choice
    }
    if ($mode === 'auto' && count($urls) === 2) {
        // Follow the device setting
        $links[] = array($urls['light'], '(prefers-color-scheme: light)', '');
        $links[] = array($urls['dark'], '(prefers-color-scheme: dark)', '');
    } elseif (isset($urls[$mode === 'dark' ? 'dark' : 'light'])) {
        $links[] = array($urls[$mode === 'dark' ? 'dark' : 'light'], '', '');
    }

    foreach ($links as $l) {
        echo '<link class="customcss-theme' . $l[2] . '" rel="stylesheet" href="' . htmlspecialchars($l[0]) . '"'
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
