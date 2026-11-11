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
        customcss_relocate();
        customcss_migrate();
        customcss_repair_empty_main();
    } catch (Throwable $e) {
        // never break a page because of the conversion; the options page shows problems
    }

    $s     = customcss_settings();
    $theme = customcss_active();
    if ($theme === '' && !$s['switch']) {
        return false;
    }

    // Logged-in user (not the shared anonymous account): the switch saves the choice in the user's
    // ResourceSpace appearance preference (the same setting as on the user preferences page)
    $user = (int) ($GLOBALS['userref'] ?? 0);
    if ($user > 0 && isset($GLOBALS['anonymous_login']) && ($GLOBALS['username'] ?? '') === $GLOBALS['anonymous_login']) {
        $user = 0;
    }

    // Mode: the user's ResourceSpace preference, else the switch cookie, else the plugin default
    $mode   = $s['default_mode'];
    $cookie = (string) ($_COOKIE['customcss_mode'] ?? '');
    if ($s['switch']) {
        $pref = null;
        if ($user > 0 && function_exists('get_config_option')
            && get_config_option(array('user' => $user), 'user_pref_appearance', $pref, null)
            && in_array($pref, array('light', 'dark', 'device'), true)) {
            $mode = $pref === 'device' ? 'auto' : (string) $pref;
        } elseif (in_array($cookie, array('auto', 'light', 'dark'), true)) {
            $mode = $cookie;
        }
    }

    $links = array(); // each entry: array(href, media, extra class)
    $urls  = array(); // light-mode.css / dark-mode.css of the active theme
    if ($theme !== '') {
        $dir  = customcss_theme_dir($theme);
        $base = customcss_themes_url() . rawurlencode($theme) . '/';

        // URL of a theme file; ?v= busts the browser cache whenever the file changes
        $url = function ($name) use ($dir, $base) {
            return $base . $name . '.css?v=' . (int) filemtime($dir . '/' . $name . '.css');
        };

        // main.css and the extra files: same in both modes (class customcss-main = kept by the switch)
        foreach (customcss_file_names($dir) as $name) {
            if (!in_array($name, array('light-mode', 'dark-mode'), true) && is_file($dir . '/' . $name . '.css')) {
                $links[] = array($url($name), '', ' customcss-main');
            }
        }
        foreach (array('light' => 'light-mode', 'dark' => 'dark-mode') as $mode_name => $file) {
            if (is_file($dir . '/' . $file . '.css')) {
                $urls[$mode_name] = $url($file);
            }
        }
        if ($mode === 'auto' && count($urls) === 2) {
            // Follow the device setting
            $links[] = array($urls['light'], '(prefers-color-scheme: light)', '');
            $links[] = array($urls['dark'], '(prefers-color-scheme: dark)', '');
        } elseif (isset($urls[$mode === 'dark' ? 'dark' : 'light'])) {
            $links[] = array($urls[$mode === 'dark' ? 'dark' : 'light'], '', '');
        }
    }
    $switch = $s['switch'];

    foreach ($links as $l) {
        echo '<link class="customcss-theme' . $l[2] . '" rel="stylesheet" href="' . htmlspecialchars($l[0]) . '"'
            . ($l[1] !== '' ? ' media="' . htmlspecialchars($l[1]) . '"' : '') . '>' . "\n";
    }

    // Where the switch saves the mode of a logged-in user (page of this plugin + CSRF token)
    $save = null;
    if ($switch && $user > 0 && function_exists('get_plugin_path')) {
        $save = array('url' => rtrim((string) get_plugin_path('customcss', true), '/') . '/pages/mode.php', 'field' => '', 'token' => '');
        if (!empty($GLOBALS['CSRF_enabled']) && function_exists('generateCSRFToken') && isset($GLOBALS['usersession'])) {
            $save['field'] = (string) ($GLOBALS['CSRF_token_identifier'] ?? '');
            $save['token'] = generateCSRFToken($GLOBALS['usersession'], 'customcss_mode');
        }
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

        // Switch logic: builds the button, swaps the theme link (if a theme is active), saves the
        // ResourceSpace appearance preference of logged-in users and remembers the choice in a cookie
        $js = <<<'JS'
(function () {
    if (window.top !== window.self) { return; } // not inside iframes (e.g. the preview)
    var urls = __URLS__;
    var mode = __MODE__;
    var save = __SAVE__;
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
    // Replaces a stylesheet link with a new one (old one removed after the new one has loaded)
    function swapLink(old, href, cls) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        if (cls) { link.className = cls; }
        link.href = href;
        link.onload = function () {
            for (var i = 0; i < old.length; i++) {
                if (old[i].parentNode) { old[i].parentNode.removeChild(old[i]); }
            }
        };
        document.head.appendChild(link);
    }
    // Reloads ResourceSpace's own light / dark styles (css/css_override.php reads the saved preference)
    function reloadSystemCss() {
        var cur = document.querySelector('link[href*="css_override.php"]');
        if (!cur) { return; }
        var href = cur.getAttribute('href').replace(/([?&])ccmode=\d+/, '$1').replace(/[?&]$/, '');
        swapLink([cur], href + (href.indexOf('?') === -1 ? '?' : '&') + 'ccmode=' + Date.now(), '');
    }
    function saveSystemMode(next) {
        if (!save || !window.fetch) { return; }
        var body = 'mode=' + encodeURIComponent(next) + '&ajax=true';
        if (save.field) { body += '&' + encodeURIComponent(save.field) + '=' + encodeURIComponent(save.token); }
        fetch(save.url, { method: 'POST', credentials: 'same-origin',
                          headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
            .then(function (r) { if (r.ok) { reloadSystemCss(); } })
            .catch(function () { /* the theme files still switch */ });
    }
    function apply(next) {
        if (urls.light && urls.dark) {
            swapLink(document.querySelectorAll('link.customcss-theme:not(.customcss-main)'), urls[next], 'customcss-theme');
        }
        saveSystemMode(next);
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
        $js = str_replace(array('__URLS__', '__MODE__', '__SAVE__'), array(json_encode((object) $urls), json_encode($mode), json_encode($save)), $js);

        echo '<style' . $nonce . '>' . $css . '</style>' . "\n";
        echo '<script' . $nonce . '>' . $js . '</script>' . "\n";
    }

    // Return false so other plugins can still use this hook
    return false;
}
