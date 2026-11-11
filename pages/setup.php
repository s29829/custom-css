<?php
include '../../../include/db.php';
include '../../../include/authenticate.php';
require_once dirname(__FILE__, 2) . '/include/functions.php';

// Admins only
if (!checkperm('a')) {
    exit($lang['error-permissiondenied']);
}

$self        = $_SERVER['PHP_SELF'];
$plugins_url = $baseurl_short . 'pages/team/team_plugins.php';
$themes_dir  = customcss_dir() . '/themes';
$message     = '';
$error       = '';

// First start (e.g. after installing from a .zip archive): create Custom/themes and the original themes
$error = customcss_prepare_dirs();
if ($error === '') {
    $created = customcss_install_defaults();
    if ($created) {
        $message = 'Original themes created in ' . $themes_dir . ': ' . implode(', ', $created) . '.';
    }
}

$theme          = customcss_slug((string) ($_POST['theme'] ?? $_GET['theme'] ?? ''));
$action         = (string) ($_POST['action'] ?? '');
$content        = null;
$loaded_version = '';
$open_box       = ''; // extra collapsible box to open after an action (Themes is always open)

// ---- Handle form actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['css_content'])) {
    $content  = str_replace("\r\n", "\n", (string) $_POST['css_content']);
    $existing = customcss_list();

    if (in_array($action, ['save', 'saveexit', 'activate'], true)) {
        if ($theme === '' || !in_array($theme, $existing, true)) {
            $error = 'No theme selected. Create one with "Save as new" first.';
        } elseif (!customcss_write_theme($theme, $content)) {
            $error = 'Cannot write the theme (check web server permissions for ' . customcss_dir() . ').';
        } elseif ($action === 'activate') {
            $message = customcss_set_active($theme)
                ? 'Saved. This theme is now active on the site.'
                : 'Saved, but the active theme could not be set (check permissions of ' . customcss_dir() . ').';
        } elseif ($action === 'saveexit') {
            redirect($plugins_url);
            exit;
        } else {
            $message = 'Saved.';
        }
    } elseif ($action === 'newversion') {
        if ($theme === '' || !in_array($theme, $existing, true)) {
            $error = 'No theme selected. Create one with "Save as new" first.';
        } else {
            $new_version = customcss_save_new_version($theme, $content);
            if ($new_version === false) {
                $error = 'Cannot save a new version (check web server permissions for ' . customcss_dir() . ').';
            } else {
                $message = 'Saved as new version: ' . customcss_version_label($theme, $new_version) . '.';
            }
        }
        $open_box = 'versions';
    } elseif ($action === 'delete_version' || $action === 'clear_history') {
        $vname = (string) ($_POST['version_name'] ?? '');
        if ($theme === '' || !in_array($theme, $existing, true)) {
            $error = 'No theme selected.';
        } elseif ($action === 'delete_version') {
            $vp = customcss_version_valid($vname) ? customcss_version_parse($vname) : null;
            if ($vp === null || !in_array($vname, customcss_versions($theme), true)) {
                $error = 'Select a version to delete.';
            } else {
                $n       = customcss_delete_version($theme, $vp['num']);
                $message = 'Version v' . $vp['num'] . ' deleted (' . $n . ' file(s)).';
            }
        } else {
            $n       = customcss_clear_history($theme);
            $message = 'Version history cleared (' . $n . ' file(s) deleted, the newest entry is kept).';
        }
        $open_box = 'versions';
    } elseif ($action === 'saveas') {
        $new = customcss_slug((string) ($_POST['new_name'] ?? ''));
        if ($new === '') {
            $error = 'Enter a theme name (letters, numbers, "-" and "_").';
        } elseif (in_array($new, $existing, true)) {
            $error = 'A theme with that name already exists.';
        } elseif (!customcss_write_theme($new, $content)) {
            $error = 'Cannot write the theme (check web server permissions for ' . customcss_dir() . ').';
        } else {
            $theme   = $new;
            $message = 'Theme "' . $new . '" created.';
        }
    } elseif ($action === 'deactivate') {
        $message = customcss_set_active('') ? 'Deactivated. No single active theme is loaded on the site.' : 'Could not deactivate (check permissions).';
    } elseif ($action === 'delete') {
        if (in_array($theme, $existing, true)) {
            customcss_snapshot($theme); // keep the last content in the version history
            if (@unlink($themes_dir . '/' . $theme . '.css')) {
                if (customcss_active() === '') {
                    customcss_set_active('');
                }
                $message = 'Theme "' . $theme . '" deleted (its version history is kept).';
            } else {
                $error = 'Could not delete the theme.';
            }
        } else {
            $error = 'Could not delete the theme.';
        }
        $theme   = '';
        $content = null;
    } elseif ($action === 'restore_default') {
        $def = customcss_slug((string) ($_POST['default_theme'] ?? ''));
        if (!in_array($def, customcss_defaults(), true)) {
            $error = 'Unknown original theme.';
        } elseif (!customcss_restore_default($def)) {
            $error = 'Could not restore the original theme (check permissions of ' . customcss_dir() . ').';
        } else {
            $theme   = $def;
            $content = null; // show the restored file
            $message = 'Original theme "' . $def . '" restored. The previous content is in the version history.';
        }
        $open_box = 'versions';
    } elseif ($action === 'savesettings') {
        $sw = [
            'switch'       => !empty($_POST['sw_enabled']),
            'main_theme'   => customcss_slug((string) ($_POST['sw_main'] ?? '')),
            'light_theme'  => customcss_slug((string) ($_POST['sw_light'] ?? '')),
            'dark_theme'   => customcss_slug((string) ($_POST['sw_dark'] ?? '')),
            'default_mode' => (string) ($_POST['sw_default'] ?? 'auto'),
        ];
        if (!in_array($sw['default_mode'], ['auto', 'light', 'dark'], true)) {
            $sw['default_mode'] = 'auto';
        }
        if ($sw['switch'] && (!in_array($sw['light_theme'], $existing, true) || !in_array($sw['dark_theme'], $existing, true))) {
            $error = 'Select an existing theme for both the light and the dark mode.';
        } elseif ($sw['main_theme'] !== '' && !in_array($sw['main_theme'], $existing, true)) {
            $error = 'The selected main theme does not exist.';
        } elseif ($sw['main_theme'] !== '' && $sw['switch'] && in_array($sw['main_theme'], [$sw['light_theme'], $sw['dark_theme']], true)) {
            $error = 'The main theme must be different from the light and the dark mode theme.';
        } elseif (!customcss_save_settings($sw)) {
            $error = 'Could not save the settings (check permissions of ' . customcss_dir() . ').';
        } else {
            $message = $sw['switch'] ? 'Switch settings saved. Users now see the light/dark button.' : 'Switch settings saved. The switch is off.';
        }
        $open_box = 'switch';
    }
}

// ---- Prepare the page ----
$themes   = customcss_list();
$active   = customcss_active();
$settings = customcss_settings();
$defaults = customcss_defaults();

if ($theme === '' || !in_array($theme, $themes, true)) {
    $theme = $active !== '' ? $active : ($themes[0] ?? '');
}

// Load an older version into the editor (GET ?version=...). It is not saved until the user clicks Save.
$version_param = (string) ($_GET['version'] ?? '');
if ($content === null && $theme !== '' && customcss_version_valid($version_param)) {
    $vfile = customcss_versions_dir($theme) . '/' . $version_param . '.css';
    if (is_readable($vfile)) {
        $content        = (string) file_get_contents($vfile);
        $loaded_version = $version_param;
        $message        = 'Version ' . customcss_version_label($theme, $version_param) . ' loaded into the editor. Click Save to make it the current version.';
        $open_box       = 'versions';
    }
}

if ($content !== null) {
    $current = $content; // keep what the user typed (also after errors)
} else {
    $file    = $themes_dir . '/' . $theme . '.css';
    $current = ($theme !== '' && is_readable($file)) ? file_get_contents($file) : '';
}

// Editor content that differs from the saved file (so leaving the page asks for confirmation)
// For actions that do not save the editor, the browser tells us whether the editor had unsaved changes.
$unsaved = $loaded_version !== ''
    || ($content !== null && $error !== '')
    || ($content !== null && in_array($action, ['savesettings', 'deactivate', 'delete_version', 'clear_history'], true)
        && ($_POST['editor_dirty'] ?? '') === '1');

$versions       = $theme !== '' ? customcss_versions($theme) : [];
$version_sel    = $loaded_version !== '' ? $loaded_version : (string) end($versions); // default: newest
$current_num    = $theme !== '' ? customcss_current_version($theme) : 1;
$default_labels = ['main-blue' => 'main-blue (shared part)', 'light-blue' => 'light-blue (light colours)', 'dark-blue' => 'dark-blue (dark colours)'];
$is_sel         = function ($a, $b) { return $a === $b ? ' selected' : ''; };

// CodeMirror assets (stored locally in the plugin). get_plugin_path() also finds the plugin in filestore/plugins.
$nonce  = isset($CSP_nonce) ? ' nonce="' . htmlspecialchars($CSP_nonce) . '"' : '';
$cm_url = (function_exists('get_plugin_path') ? rtrim((string) get_plugin_path('customcss', true), '/') : $baseurl . '/plugins/customcss')
    . '/lib/codemirror/';
$cm_css = ['codemirror.min.css', 'foldgutter.min.css', 'show-hint.min.css', 'lint.min.css',
           'theme-cc-dark-blue.css', 'theme-dracula.min.css', 'theme-monokai.min.css', 'theme-nord.min.css'];
$cm_js  = ['codemirror.min.js', 'css-mode.min.js', 'closebrackets.min.js', 'matchbrackets.min.js',
           'active-line.min.js', 'comment.min.js', 'foldcode.min.js', 'foldgutter.min.js',
           'brace-fold.min.js', 'show-hint.min.js', 'css-hint.min.js', 'lint.min.js'];

include '../../../include/header.php';

$active_label = $settings['switch']
    ? 'light/dark switch (' . $settings['light_theme'] . ' / ' . $settings['dark_theme'] . ')'
    : ($active !== '' ? $active : 'none');
if ($settings['main_theme'] !== '' && in_array($settings['main_theme'], $themes, true) && ($settings['switch'] || $active !== '')) {
    $active_label = $settings['main_theme'] . ' + ' . $active_label;
}
?>
<div class="BasicsBox cc-page">
    <h1>Custom CSS</h1>
    <?php
    renderBreadcrumbs([
        ['title' => $lang['systemsetup'],   'href' => $baseurl_short . 'pages/admin/admin_home.php', 'menu' => true],
        ['title' => $lang['pluginmanager'], 'href' => $plugins_url],
        ['title' => 'Custom CSS'],
    ]);
    ?>

    <?php if ($message) { ?><div class="PageInformal"><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if ($error) { ?><div class="FormError"><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <?php foreach ($cm_css as $f) { ?>
        <link rel="stylesheet" href="<?php echo $cm_url . $f; ?>">
    <?php } ?>
    <style<?php echo $nonce; ?>>
        /* Two columns on desktop (editor 2/3, settings 1/3); a single column on smaller screens */
        .cc-layout { display: block; }
        @media (min-width: 1100px) {
            .cc-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 24px; align-items: start; }
        }
        .CodeMirror { border: 1px solid #ccc; height: 70vh; min-height: 420px; font-size: 13px; }
        .cc-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0.8em 0; }
        .cc-box { margin: 0 0 14px; padding: 10px 14px; border: 1px solid rgba(128, 128, 128, 0.45); border-radius: 6px; }
        .cc-box > summary { cursor: pointer; list-style: none; display: flex; align-items: center; gap: 8px; }
        .cc-box > summary::-webkit-details-marker { display: none; }
        .cc-box > summary::before { content: "\25B8"; display: inline-block; transition: transform 0.15s; }
        .cc-box[open] > summary::before { transform: rotate(90deg); }
        .cc-box[open] > summary { margin-bottom: 8px; }
        .cc-box h3 { margin: 0; font-size: 1.05em; display: inline; }
        .cc-box h4 { margin: 14px 0 6px; padding-top: 10px; font-size: 1em; border-top: 1px solid rgba(128, 128, 128, 0.3); }
        .cc-box p { margin: 0.5em 0; }
        .cc-field { display: block; margin: 0.6em 0; }
        .cc-field > span { display: block; margin-bottom: 3px; }
        .cc-side select, .cc-side input[type=text] { width: 100%; max-width: 100%; box-sizing: border-box; }
        .cc-side input[type=checkbox] { width: auto; }
        .cc-path { word-break: break-all; }
        #preview-box { margin-top: 1.5em; }
        #preview-box[hidden] { display: none; }
        #preview-path { width: 320px; max-width: 100%; }
        #preview-frame { width: 100%; height: 600px; border: 1px solid #ccc; background: #fff; }
        #lint-status.lint-ok { color: #2e7d32; }
        #lint-status.lint-warn { color: #b26a00; }
        #lint-status.lint-bad { color: #c62828; }
    </style>

    <form id="css-form" method="post" action="<?php echo htmlspecialchars($self); ?>">
        <?php generateFormToken('customcss'); ?>
        <input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme); ?>">
        <input type="hidden" name="editor_dirty" id="editor-dirty" value="0">

        <div class="cc-layout">

            <!-- LEFT COLUMN: editor, syntax check, buttons -->
            <div class="cc-main">
                <textarea id="css_content" name="css_content" spellcheck="false"><?php echo htmlspecialchars($current); ?></textarea>
                <p>Syntax check: <b id="lint-status" class="lint-ok">checking...</b></p>
                <div class="cc-row">
                    <button type="submit" name="action" value="save" id="btn-save"<?php echo $theme === '' ? ' disabled' : ''; ?>>Save</button>
                    <button type="submit" name="action" value="saveexit"<?php echo $theme === '' ? ' disabled' : ''; ?>>Save &amp; Exit</button>
                    <button type="submit" name="action" value="newversion"<?php echo $theme === '' ? ' disabled' : ''; ?> title="Saves the editor content as version v<?php echo (int) $current_num + 1; ?> (file name gets the version number, date and time)">Save as new version</button>
                    <button type="button" id="btn-preview">Preview</button>
                    <button type="button" id="btn-exit">Exit</button>
                </div>
            </div>

            <!-- RIGHT COLUMN: collapsible settings boxes -->
            <div class="cc-side">
                <details class="cc-box" data-box="themes" open>
                    <summary><h3>Themes</h3></summary>
                    <?php if ($themes) { ?>
                        <label class="cc-field">
                            <span>Theme</span>
                            <select id="theme-select">
                                <?php foreach ($themes as $t) { ?>
                                    <option value="<?php echo htmlspecialchars($t); ?>"<?php echo $is_sel($t, $theme); ?>>
                                        <?php echo htmlspecialchars($t . ($t === $active ? ' (active)' : '')); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </label>
                        <div class="cc-row">
                            <?php if ($theme === $active) { ?>
                                <button type="submit" name="action" value="deactivate">Deactivate</button>
                            <?php } else { ?>
                                <button type="submit" name="action" value="activate">Save &amp; activate</button>
                            <?php } ?>
                            <button type="submit" name="action" value="delete">Delete theme</button>
                        </div>
                    <?php } else { ?>
                        <p><i>No themes yet. Type a name below and click "Save as new".</i></p>
                    <?php } ?>
                    <p>Active on the site: <b><?php echo htmlspecialchars($active_label); ?></b></p>

                    <h4>Add a new theme</h4>
                    <p>Saves the current editor content under a new name.</p>
                    <input type="text" name="new_name" id="new_name" placeholder="e.g. dark-blue-2" maxlength="40">
                    <div class="cc-row">
                        <button type="submit" name="action" value="saveas" id="btn-saveas">Save as new</button>
                    </div>

                    <h4>Theme file</h4>
                    <p class="cc-path"><code><?php echo htmlspecialchars($theme !== '' ? $themes_dir . '/' . $theme . '.css' : '(none)'); ?></code></p>
                </details>

                <details class="cc-box" data-box="editor"<?php echo $open_box === 'editor' ? ' open' : ''; ?>>
                    <summary><h3>Editor</h3></summary>
                    <p>Shortcuts: <b>Ctrl+S</b> save, <b>Ctrl+Space</b> suggestions, <b>Ctrl+/</b> toggle comment, <b>Tab</b> indent.</p>
                    <label class="cc-field">
                        <span>Editor colours</span>
                        <select id="editor-theme">
                            <option value="auto">Auto (follows the page: light or dark)</option>
                            <option value="light">Light</option>
                            <option value="cc-dark-blue">Dark blue</option>
                            <option value="dracula">Dracula</option>
                            <option value="monokai">Monokai</option>
                            <option value="nord">Nord</option>
                        </select>
                    </label>
                </details>

                <details class="cc-box" data-box="versions"<?php echo $open_box === 'versions' ? ' open' : ''; ?>>
                    <summary><h3>Version history</h3></summary>
                    <p>Current version of "<?php echo htmlspecialchars($theme); ?>": <b>v<?php echo (int) $current_num; ?></b></p>
                    <p>Every Save stores the previous content under the current version number. "Save as new version" (below the editor) starts the next number. The last 30 entries are kept.</p>
                    <?php if ($versions) { ?>
                        <select name="version_name" id="version-select">
                            <?php foreach ($versions as $v) { ?>
                                <option value="<?php echo htmlspecialchars($v); ?>" data-num="<?php echo (int) customcss_version_parse($v)['num']; ?>"<?php echo $is_sel($v, $version_sel); ?>><?php echo htmlspecialchars(customcss_version_label($theme, $v)); ?></option>
                            <?php } ?>
                        </select>
                        <div class="cc-row">
                            <button type="button" id="btn-load-version">Load into editor</button>
                            <button type="submit" name="action" value="delete_version">Delete this version</button>
                            <button type="submit" name="action" value="clear_history"<?php echo count($versions) < 2 ? ' disabled' : ''; ?>>Delete all history</button>
                        </div>
                        <p><i>Loading only puts the version in the editor. Click Save to make it the current version (the current content is then added to the history).
                            "Delete this version" removes all stored files with the selected version number; "Delete all history" removes everything except the newest entry.</i></p>
                    <?php } else { ?>
                        <p><i>No previous versions yet.</i></p>
                    <?php } ?>

                    <?php if ($defaults) { ?>
                        <h4>Original themes</h4>
                        <p>Restoring replaces the theme with the original shipped with the plugin (or recreates it if it was deleted). The current content is kept in the version history.</p>
                        <select name="default_theme" id="default-select">
                            <?php foreach ($defaults as $d) { ?>
                                <option value="<?php echo htmlspecialchars($d); ?>"<?php echo $is_sel($d, $theme); ?>><?php echo htmlspecialchars($default_labels[$d] ?? $d); ?></option>
                            <?php } ?>
                        </select>
                        <div class="cc-row">
                            <button type="submit" name="action" value="restore_default" id="btn-restore">Restore original</button>
                        </div>
                    <?php } ?>
                </details>

                <details class="cc-box" data-box="switch"<?php echo $open_box === 'switch' ? ' open' : ''; ?>>
                    <summary><h3>Light / dark mode switch</h3></summary>
                    <p>When enabled, every page shows a small round button that lets each user switch between a light and a dark theme. The choice is remembered in the user's browser. While the switch is enabled it replaces the single "active theme".</p>
                    <p><label><input type="checkbox" name="sw_enabled" value="1"<?php echo $settings['switch'] ? ' checked' : ''; ?>> Enable the switch</label></p>
                    <label class="cc-field">
                        <span>Main theme (shared part)</span>
                        <select name="sw_main">
                            <option value=""<?php echo $is_sel('', $settings['main_theme']); ?>>- none -</option>
                            <?php foreach ($themes as $t) { ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"<?php echo $is_sel($t, $settings['main_theme']); ?>><?php echo htmlspecialchars($t); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <p><i>The main theme holds what is the same in light and dark mode (shapes, sizes, fonts, login page, home page banner). It is loaded before the light or dark theme, and also before the single active theme while the switch is off.</i></p>
                    <label class="cc-field">
                        <span>Light mode theme</span>
                        <select name="sw_light">
                            <?php foreach ($themes as $t) { ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"<?php echo $is_sel($t, $settings['light_theme']); ?>><?php echo htmlspecialchars($t); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <label class="cc-field">
                        <span>Dark mode theme</span>
                        <select name="sw_dark">
                            <?php foreach ($themes as $t) { ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"<?php echo $is_sel($t, $settings['dark_theme']); ?>><?php echo htmlspecialchars($t); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <label class="cc-field">
                        <span>Mode for users who have not chosen yet</span>
                        <select name="sw_default">
                            <option value="auto"<?php echo $is_sel('auto', $settings['default_mode']); ?>>Follow the device setting</option>
                            <option value="light"<?php echo $is_sel('light', $settings['default_mode']); ?>>Light</option>
                            <option value="dark"<?php echo $is_sel('dark', $settings['default_mode']); ?>>Dark</option>
                        </select>
                    </label>
                    <div class="cc-row">
                        <button type="submit" name="action" value="savesettings">Save switch settings</button>
                    </div>
                </details>
            </div>

        </div>
    </form>

    <!-- Live preview panel (full width, outside the form so Enter does not submit it) -->
    <div id="preview-box" hidden>
        <p>
            Preview page:
            <input type="text" id="preview-path" value="pages/home.php">
            <button type="button" id="btn-preview-reload">Reload</button>
            <button type="button" id="btn-preview-close">Close preview</button>
        </p>
        <p><i>The preview shows the current editor content instead of the site's theme (the main theme stays on, unless you are editing it). Nothing is saved until you click Save.</i></p>
        <iframe id="preview-frame"></iframe>
    </div>

    <?php foreach ($cm_js as $f) { ?>
        <script src="<?php echo $cm_url . $f; ?>"<?php echo $nonce; ?>></script>
    <?php } ?>
    <script<?php echo $nonce; ?>>
    (function () {
        var dirty = <?php echo $unsaved ? 'true' : 'false'; ?>;
        var pluginsUrl = <?php echo json_encode($plugins_url); ?>;
        var selfUrl = <?php echo json_encode($self); ?>;
        var baseUrlShort = <?php echo json_encode($baseurl_short); ?>;
        var currentTheme = <?php echo json_encode($theme); ?>;
        var mainTheme = <?php echo json_encode($settings['main_theme']); ?>;
        var form = document.getElementById('css-form');
        var previewBox = document.getElementById('preview-box');
        var previewFrame = document.getElementById('preview-frame');
        var previewPath = document.getElementById('preview-path');
        var statusEl = document.getElementById('lint-status');
        var previewTimer = null;

        function saveNow() { document.getElementById('btn-save').click(); }

        // ---------------------------------------------------------------
        // Syntax checker
        // Checks brackets, comments, strings, declarations and (through the
        // browser's CSS.supports) property names and values.
        // Returns annotations for the CodeMirror lint addon.
        // ---------------------------------------------------------------
        function lintCss(text) {
            var found = [];
            var lineStarts = [0];
            var k;
            for (k = 0; k < text.length; k++) {
                if (text.charAt(k) === '\n') { lineStarts.push(k + 1); }
            }
            function pos(idx) {
                var lo = 0, hi = lineStarts.length - 1, mid;
                while (lo < hi) {
                    mid = (lo + hi + 1) >> 1;
                    if (lineStarts[mid] <= idx) { lo = mid; } else { hi = mid - 1; }
                }
                return { line: lo, ch: idx - lineStarts[lo] };
            }
            function add(from, to, message, severity) {
                if (to <= from) { to = from + 1; }
                found.push({ from: pos(from), to: pos(to), message: message, severity: severity });
            }

            // Step 1: mask comments and string contents (same length, so indexes stay valid)
            var m = '', i = 0, n = text.length, c, q, j;
            while (i < n) {
                c = text.charAt(i);
                if (c === '/' && text.charAt(i + 1) === '*') {
                    j = text.indexOf('*/', i + 2);
                    if (j === -1) {
                        add(i, i + 2, 'Unclosed comment (missing */)', 'error');
                        m += text.slice(i).replace(/[^\n]/g, ' ');
                        i = n;
                    } else {
                        m += text.slice(i, j + 2).replace(/[^\n]/g, ' ');
                        i = j + 2;
                    }
                } else if (c === '"' || c === "'") {
                    q = c;
                    j = i + 1;
                    while (j < n && text.charAt(j) !== q && text.charAt(j) !== '\n') {
                        if (text.charAt(j) === '\\') { j++; }
                        j++;
                    }
                    if (j >= n || text.charAt(j) === '\n') {
                        add(i, Math.min(j, n), 'Unclosed string', 'error');
                        m += q + text.slice(i + 1, j).replace(/[^\n]/g, 'x');
                        i = j;
                    } else {
                        m += q + text.slice(i + 1, j).replace(/[^\n]/g, 'x') + q;
                        i = j + 1;
                    }
                } else {
                    m += c;
                    i++;
                }
            }

            // Step 2: check structure and declarations
            var stack = [], parens = [], start = 0, ch;

            function checkDecl(s, e) {
                var raw = m.slice(s, e);
                if (!/\S/.test(raw)) { return; }
                var first = s + raw.search(/\S/);
                var body = raw.trim();
                if (body.charAt(0) === '@') { return; }
                if (!stack.length) {
                    add(first, e, 'Unexpected text outside a rule', 'error');
                    return;
                }
                var colon = body.indexOf(':');
                if (colon < 1) {
                    add(first, e, "Missing property name or ':' in declaration", 'error');
                    return;
                }
                var absColon = first + colon;
                var prop = text.slice(first, absColon).trim();
                var valueRaw = text.slice(absColon + 1, e);
                var value = valueRaw.replace(/\s*!\s*important\s*$/i, '').trim();
                if (prop.indexOf('--') === 0) { return; }
                if (/\n\s*[-a-zA-Z]+\s*:/.test(valueRaw)) {
                    add(first, e, "Possible missing ';' at the end of the previous line", 'error');
                    return;
                }
                if (value === '') {
                    add(first, e, "Empty value for '" + prop + "'", 'error');
                    return;
                }
                if (stack[stack.length - 1].skip) { return; }
                if (prop.charAt(0) === '-' || !(window.CSS && CSS.supports)) { return; }
                if (!CSS.supports(prop, value)) {
                    var hint = /^([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value) ? ' Did you mean #' + value + '?' : '';
                    add(first, e, "Invalid value or unknown property '" + prop + "'." + hint, 'warning');
                }
            }

            for (i = 0; i < m.length; i++) {
                ch = m.charAt(i);
                if (ch === '(') {
                    parens.push(i);
                } else if (ch === ')') {
                    if (!parens.length) { add(i, i + 1, "Unmatched ')'", 'error'); } else { parens.pop(); }
                } else if (parens.length) {
                    continue;
                } else if (ch === '{') {
                    var prelude = m.slice(start, i).trim();
                    var parentSkip = stack.length ? stack[stack.length - 1].skip : false;
                    stack.push({
                        idx: i,
                        // At-rules whose contents are descriptors, not normal properties
                        skip: parentSkip || /^@(font-face|page|property|counter-style|font-palette-values|view-transition)/i.test(prelude)
                    });
                    start = i + 1;
                } else if (ch === '}') {
                    if (!stack.length) {
                        add(i, i + 1, "Unexpected '}' (no matching '{')", 'error');
                    } else {
                        checkDecl(start, i);
                        stack.pop();
                    }
                    start = i + 1;
                } else if (ch === ';') {
                    checkDecl(start, i);
                    start = i + 1;
                }
            }
            stack.forEach(function (o) { add(o.idx, o.idx + 1, "Unclosed '{' (missing '}')", 'error'); });
            parens.forEach(function (idx) { add(idx, idx + 1, "Unclosed '('", 'error'); });
            return found;
        }

        function countProblems(list) {
            var r = { errors: 0, warnings: 0 };
            list.forEach(function (a) { if (a.severity === 'error') { r.errors++; } else { r.warnings++; } });
            return r;
        }

        function updateStatus(list) {
            var r = countProblems(list);
            if (!r.errors && !r.warnings) {
                statusEl.textContent = 'no problems found';
                statusEl.className = 'lint-ok';
            } else {
                statusEl.textContent = r.errors + ' error(s), ' + r.warnings + ' warning(s)';
                statusEl.className = r.errors ? 'lint-bad' : 'lint-warn';
            }
        }

        function lintForEditor(text) {
            var list = lintCss(text);
            updateStatus(list);
            return list;
        }

        // ---------------------------------------------------------------
        // Editor
        // ---------------------------------------------------------------
        var editor = CodeMirror.fromTextArea(document.getElementById('css_content'), {
            mode: 'css',
            theme: 'default',
            lineNumbers: true,
            indentUnit: 4,
            tabSize: 4,
            matchBrackets: true,
            autoCloseBrackets: true,
            styleActiveLine: true,
            foldGutter: true,
            lint: { getAnnotations: lintForEditor, delay: 400 },
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter', 'CodeMirror-lint-markers'],
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Ctrl-S': saveNow,
                'Cmd-S': saveNow,
                'Ctrl-/': 'toggleComment',
                'Cmd-/': 'toggleComment',
                Tab: function (cm) {
                    if (cm.somethingSelected()) { cm.indentSelection('add'); }
                    else { cm.replaceSelection('    ', 'end'); }
                }
            }
        });
        updateStatus(lintCss(editor.getValue()));

        // ---------------------------------------------------------------
        // Editor colours: Auto follows the page (dark page = dark editor).
        // The choice is remembered in this browser (localStorage).
        // ---------------------------------------------------------------
        function bgIsDark(el) {
            var parts = (window.getComputedStyle(el).backgroundColor || '').match(/[\d.]+/g);
            if (!parts || parts.length < 3) { return null; }
            if (parts.length > 3 && parseFloat(parts[3]) === 0) { return null; } // transparent
            return (0.2126 * parts[0] + 0.7152 * parts[1] + 0.0722 * parts[2]) / 255 < 0.5;
        }
        function pageIsDark() {
            var r = null;
            try {
                r = bgIsDark(document.body);
                if (r === null) { r = bgIsDark(document.documentElement); }
            } catch (e) { r = null; }
            if (r === null) { r = !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
            return r;
        }
        function applyEditorTheme(choice) {
            var name = choice;
            if (choice === 'auto') { name = pageIsDark() ? 'cc-dark-blue' : 'default'; }
            else if (choice === 'light') { name = 'default'; }
            editor.setOption('theme', name);
        }
        var editorThemeSelect = document.getElementById('editor-theme');
        var savedEditorTheme = 'auto';
        try { savedEditorTheme = window.localStorage.getItem('customcss_editor_theme') || 'auto'; } catch (e) { /* storage not available */ }
        if (editorThemeSelect) {
            editorThemeSelect.value = savedEditorTheme;
            if (editorThemeSelect.value !== savedEditorTheme) { savedEditorTheme = 'auto'; editorThemeSelect.value = 'auto'; }
            editorThemeSelect.addEventListener('change', function () {
                try { window.localStorage.setItem('customcss_editor_theme', editorThemeSelect.value); } catch (e) { /* ignore */ }
                applyEditorTheme(editorThemeSelect.value);
            });
        }
        applyEditorTheme(savedEditorTheme);

        // Show suggestions while typing
        editor.on('inputRead', function (cm, change) {
            if (cm.state.completionActive) { return; }
            var typed = change.text[0];
            if (typed && /^[a-zA-Z-]$/.test(typed)) {
                cm.showHint({ completeSingle: false });
            }
        });

        // ---------------------------------------------------------------
        // Preview
        // Applies the editor content to the preview iframe without saving it.
        // The site's theme link(s) are switched off inside the frame, so only the
        // editor content is visible. Uses constructable stylesheets (not blocked
        // by CSP) with a <style> fallback.
        // ---------------------------------------------------------------
        function injectCss() {
            try {
                var doc = previewFrame.contentDocument;
                var win = previewFrame.contentWindow;
                if (!doc || !win || !doc.head) { return; }
                var css = editor.getValue();

                // Editing the main theme: switch off the site's main theme and keep the light/dark theme.
                // Editing any other theme: keep the main theme and switch off the light/dark (active) theme.
                var editingMain = mainTheme !== '' && currentTheme === mainTheme;
                [].forEach.call(doc.querySelectorAll('link[rel="stylesheet"][href*="Custom/themes/"]'), function (l) {
                    var isMain = (' ' + l.className + ' ').indexOf(' customcss-main ') !== -1;
                    if (isMain !== editingMain) { return; }
                    l.disabled = true;
                    if (l.sheet) { l.sheet.disabled = true; }
                });

                if ('adoptedStyleSheets' in doc && win.CSSStyleSheet && win.CSSStyleSheet.prototype.replaceSync) {
                    if (!previewFrame._sheet || previewFrame._sheetDoc !== doc) {
                        previewFrame._sheet = new win.CSSStyleSheet();
                        previewFrame._sheetDoc = doc;
                        doc.adoptedStyleSheets = doc.adoptedStyleSheets.concat([previewFrame._sheet]);
                    }
                    previewFrame._sheet.replaceSync(css);
                } else {
                    var el = doc.getElementById('customcss-preview-style');
                    if (!el) {
                        el = doc.createElement('style');
                        el.id = 'customcss-preview-style';
                        doc.head.appendChild(el);
                    }
                    el.textContent = css;
                }
            } catch (e) { /* ignore timing errors */ }
        }

        function loadPreview() {
            var path = (previewPath.value || '').replace(/^\/+/, '');
            if (!path || /^[a-z][a-z0-9+.-]*:/i.test(path)) { path = 'pages/home.php'; }
            previewPath.value = path;
            previewBox.hidden = false;
            previewFrame.src = baseUrlShort + path;
        }

        // Re-apply the CSS every time the preview frame loads a page
        previewFrame.addEventListener('load', injectCss);

        document.getElementById('btn-preview').addEventListener('click', function () {
            loadPreview();
            previewBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        document.getElementById('btn-preview-reload').addEventListener('click', loadPreview);
        document.getElementById('btn-preview-close').addEventListener('click', function () {
            previewBox.hidden = true;
            previewFrame.removeAttribute('src');
        });
        previewPath.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); loadPreview(); }
        });

        // Pressing Enter in the "new theme" field must only trigger "Save as new"
        document.getElementById('new_name').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btn-saveas').click(); }
        });

        // ---------------------------------------------------------------
        // Change tracking, theme switching, version loading, confirmations
        // ---------------------------------------------------------------
        editor.on('change', function () {
            dirty = true;
            if (!previewBox.hidden) {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(injectCss, 300);
            }
        });

        var select = document.getElementById('theme-select');
        if (select) {
            select.addEventListener('change', function () {
                if (dirty && !confirm('You have unsaved changes. Switch theme without saving?')) {
                    select.value = currentTheme;
                    return;
                }
                dirty = false;
                window.location.href = selfUrl + '?theme=' + encodeURIComponent(select.value);
            });
        }

        var loadBtn = document.getElementById('btn-load-version');
        if (loadBtn) {
            loadBtn.addEventListener('click', function () {
                var v = document.getElementById('version-select').value;
                if (dirty && !confirm('You have unsaved changes. Replace the editor content with the selected version?')) { return; }
                dirty = false;
                window.location.href = selfUrl + '?theme=' + encodeURIComponent(currentTheme) + '&version=' + encodeURIComponent(v);
            });
        }

        form.addEventListener('submit', function (e) {
            var act = (e.submitter && e.submitter.value) || '';
            if (act === 'save' || act === 'saveexit' || act === 'saveas' || act === 'activate' || act === 'newversion') {
                var r = countProblems(lintCss(editor.getValue()));
                if (r.errors && !confirm('The CSS contains ' + r.errors + ' syntax error(s). Save anyway?')) {
                    e.preventDefault();
                    return;
                }
            }
            if (act === 'delete' && !confirm('Delete theme "' + currentTheme + '"? Its version history is kept.')) {
                e.preventDefault();
                return;
            }
            if (act === 'delete_version') {
                var opt = document.getElementById('version-select').selectedOptions[0];
                if (!opt || !confirm('Delete ALL stored files of version v' + opt.getAttribute('data-num') + ' of "' + currentTheme + '"? This cannot be undone.')) {
                    e.preventDefault();
                    return;
                }
            }
            if (act === 'clear_history' && !confirm('Delete the whole version history of "' + currentTheme + '" except the newest entry? This cannot be undone.')) {
                e.preventDefault();
                return;
            }
            if (act === 'restore_default') {
                var d = document.getElementById('default-select').value;
                if (!confirm('Restore the original theme "' + d + '"? The current content of that theme is kept in its version history.')) {
                    e.preventDefault();
                    return;
                }
            }
            document.getElementById('editor-dirty').value = dirty ? '1' : '0';
            dirty = false;
        });

        document.getElementById('btn-exit').addEventListener('click', function () {
            if (dirty && !confirm('You have unsaved changes. Exit without saving?')) { return; }
            dirty = false;
            window.location.href = pluginsUrl;
        });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    })();
    </script>
</div>
<?php
include '../../../include/footer.php';
