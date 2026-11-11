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
$message     = '';
$notes       = array(); // messages of the first start (folders, original themes, conversion)
$open_box    = '';      // extra collapsible box to open after an action (Themes is always open)

// ---- First start: folders, conversion of the old layout, original themes ----
$error = customcss_prepare_dirs();
if ($error === '') {
    $notes   = customcss_migrate();
    $created = customcss_install_defaults();
    if ($created) {
        $notes[] = 'Original themes created: ' . implode(', ', $created) . '.';
    }
}

$theme       = customcss_slug((string) ($_POST['theme'] ?? $_GET['theme'] ?? ''));
$action      = (string) ($_POST['action'] ?? '');
$posted      = null;  // files sent by the editor (name => CSS)
$keep_posted = false; // show the sent files again (not saved)

// ---- Handle form actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files_json'])) {
    $perr     = '';
    $posted   = customcss_clean_files(json_decode((string) $_POST['files_json'], true), $perr);
    $existing = customcss_list();
    $dirty    = ($_POST['editor_dirty'] ?? '') === '1';

    if ($action === 'save') {
        if (!in_array($theme, $existing, true)) {
            $error = 'No theme selected. Create one with "Save as new theme" first.';
        } elseif ($posted === null) {
            $error = $perr;
        } else {
            $v = customcss_save_theme($theme, $posted);
            if ($v === false) {
                $error       = 'Cannot save the theme (check web server permissions for ' . customcss_dir() . ').';
                $keep_posted = true;
            } else {
                $message = 'All files saved as version ' . customcss_version_label($theme, $v) . '.';
            }
        }
        $keep_posted = $keep_posted || ($error !== '' && $posted !== null);
    } elseif ($action === 'saveas') {
        $new = customcss_slug((string) ($_POST['new_name'] ?? ''));
        if ($new === '') {
            $error = 'Enter a theme name (letters, numbers, "-" and "_").';
        } elseif (in_array($new, $existing, true)) {
            $error = 'A theme with that name already exists.';
        } elseif ($posted === null) {
            $error = $perr;
        } elseif (customcss_save_theme($new, $posted) === false) {
            $error = 'Cannot create the theme (check web server permissions for ' . customcss_dir() . ').';
        } else {
            $theme   = $new;
            $message = 'Theme "' . $new . '" created from the editor content (version v1).';
        }
        $keep_posted = $error !== '' && $posted !== null;
    } elseif ($action === 'activate' || $action === 'deactivate') {
        if ($action === 'activate' && !in_array($theme, $existing, true)) {
            $error = 'Select a theme.';
        } elseif (!customcss_set_active($action === 'activate' ? $theme : '')) {
            $error = 'Could not save the settings (check permissions of ' . customcss_dir() . ').';
        } else {
            $message = $action === 'activate'
                ? 'Theme "' . $theme . '" is now active on the site (saved version - unsaved editor changes are not used).'
                : 'Deactivated. No theme is loaded on the site.';
        }
        $keep_posted = $dirty && $posted !== null;
    } elseif ($action === 'delete') {
        if (customcss_delete_theme($theme)) {
            $message = 'Theme "' . $theme . '" deleted (its version history is kept).';
            $theme   = '';
        } else {
            $error = 'Could not delete the theme.';
        }
    } elseif ($action === 'delete_version' || $action === 'clear_history') {
        $vname = (string) ($_POST['version_name'] ?? '');
        if (!in_array($theme, $existing, true)) {
            $error = 'No theme selected.';
        } elseif ($action === 'delete_version') {
            $message = customcss_delete_version($theme, $vname) ? 'Version ' . $vname . ' deleted.' : '';
            $error   = $message === '' ? 'Select a version to delete.' : '';
        } else {
            $message = 'Version history cleared (' . customcss_clear_history($theme) . ' version(s) deleted, the newest is kept).';
        }
        $keep_posted = $dirty && $posted !== null;
        $open_box    = 'versions';
    } elseif ($action === 'restore_default') {
        $def = customcss_slug((string) ($_POST['default_theme'] ?? ''));
        $v   = customcss_restore_default($def);
        if ($v === false) {
            $error = 'Could not restore the original theme (check permissions of ' . customcss_dir() . ').';
        } else {
            $theme   = $def;
            $message = 'Original theme "' . $def . '" restored as version ' . customcss_version_label($def, $v) . '.';
        }
        $open_box = 'versions';
    } elseif ($action === 'savesettings') {
        $s                 = customcss_settings();
        $s['switch']       = !empty($_POST['sw_enabled']);
        $s['default_mode'] = in_array($_POST['sw_default'] ?? '', array('auto', 'light', 'dark'), true) ? $_POST['sw_default'] : 'auto';
        if (!customcss_save_settings($s)) {
            $error = 'Could not save the settings (check permissions of ' . customcss_dir() . ').';
        } else {
            $message = $s['switch'] ? 'Switch settings saved. Users now see the light/dark button.' : 'Switch settings saved. The switch is off.';
        }
        $keep_posted = $dirty && $posted !== null;
        $open_box    = 'switch';
    }
}

// ---- Prepare the page ----
$themes   = customcss_list();
$settings = customcss_settings();
$active   = customcss_active();
$defaults = customcss_defaults();
if (!in_array($theme, $themes, true)) {
    $theme = $active !== '' ? $active : ($themes[0] ?? '');
}
$saved_files = $theme !== '' ? customcss_read_theme($theme) : array('main' => '', 'light-mode' => '', 'dark-mode' => '');

// Load a stored version into the editor (GET ?version=...). It is saved only with "Save".
$loaded_version = '';
$files          = $saved_files;
$version_param  = (string) ($_GET['version'] ?? '');
if ($keep_posted) {
    $files = $posted;
} elseif ($theme !== '' && $version_param !== '' && $action === '') {
    $vf = customcss_version_files($theme, $version_param);
    if ($vf !== null) {
        $files          = $vf;
        $loaded_version = $version_param;
        $message        = 'Version ' . customcss_version_label($theme, $version_param) . ' loaded into the editor. Click "Save" to store it as the newest version.';
        $open_box       = 'versions';
    }
}

$versions     = $theme !== '' ? customcss_versions($theme) : array();
$version_sel  = $loaded_version !== '' ? $loaded_version : (string) end($versions);
$current_num  = $theme !== '' ? customcss_current_version($theme) : 0;
$is_sel       = function ($a, $b) { return $a === $b ? ' selected' : ''; };
$h            = function ($s) { return htmlspecialchars((string) $s); };
$mode_labels  = array('auto' => 'follows the device setting', 'light' => 'light', 'dark' => 'dark');
$active_label = $active !== ''
    ? $active . ' (' . ($settings['switch'] ? 'light/dark switch on' : 'switch off') . ', default mode: ' . $mode_labels[$settings['default_mode']] . ')'
    : 'none';

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

    <?php foreach ($notes as $n) { ?><div class="PageInformal"><?php echo $h($n); ?></div><?php } ?>
    <?php if ($message) { ?><div class="PageInformal"><?php echo $h($message); ?></div><?php } ?>
    <?php if ($error) { ?><div class="FormError"><?php echo $h($error); ?></div><?php } ?>

    <?php foreach ($cm_css as $f) { ?>
        <link rel="stylesheet" href="<?php echo $cm_url . $f; ?>">
    <?php } ?>
    <style<?php echo $nonce; ?>>
        /* Two columns on desktop (editor 2/3, settings 1/3); a single column on smaller screens */
        .cc-layout { display: block; }
        @media (min-width: 1100px) {
            .cc-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 24px; align-items: start; }
        }
        .CodeMirror { border: 1px solid #ccc; height: 66vh; min-height: 420px; font-size: 13px; }
        .cc-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 0.8em 0; }
        .cc-tabs { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 2px; border-bottom: 1px solid #ccc; }
        .cc-tab { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #ccc; border-bottom: none;
                  border-radius: 6px 6px 0 0; background: rgba(128, 128, 128, 0.12); color: inherit; cursor: pointer; font: inherit;
                  box-shadow: none; min-height: 0; margin: 0 0 -1px; }
        .cc-tab[aria-selected=true] { background: transparent; font-weight: bold; border-bottom: 1px solid transparent; }
        .cc-tab .cc-dot { color: #b26a00; font-weight: bold; }
        .cc-tab .cc-x { opacity: 0.6; padding: 0 2px; }
        .cc-tab .cc-x:hover { opacity: 1; color: #c62828; }
        .cc-tab-add { padding: 6px 10px; margin: 0 0 4px 6px; min-height: 0; }
        .cc-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; margin: 6px 0; }
        .cc-toolbar .cc-info { opacity: 0.8; font-size: 0.9em; }
        .cc-toolbar button { min-height: 0; padding: 3px 10px; }
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
        .cc-hint { opacity: 0.8; font-size: 0.9em; }
        #preview-box { margin-top: 1.5em; }
        #preview-box[hidden] { display: none; }
        #preview-path { width: 320px; max-width: 100%; }
        #preview-frame { width: 100%; height: 600px; border: 1px solid #ccc; background: #fff; }
        #lint-status.lint-ok { color: #2e7d32; }
        #lint-status.lint-warn { color: #b26a00; }
        #lint-status.lint-bad { color: #c62828; }
    </style>

    <form id="css-form" method="post" action="<?php echo $h($self); ?>">
        <?php generateFormToken('customcss'); ?>
        <input type="hidden" name="theme" value="<?php echo $h($theme); ?>">
        <input type="hidden" name="files_json" id="files-json" value="">
        <input type="hidden" name="editor_dirty" id="editor-dirty" value="0">

        <div class="cc-layout">

            <!-- LEFT COLUMN: file tabs, editor, syntax check, buttons -->
            <div class="cc-main">
                <div class="cc-tabs" id="cc-tabs" role="tablist" aria-label="Theme files"></div>
                <div class="cc-toolbar">
                    <span class="cc-info" id="cc-file-info"></span>
                    <span>
                        <button type="button" id="btn-undo" title="Undo (Ctrl+Z / Cmd+Z)">&#8630; Undo</button>
                        <button type="button" id="btn-redo" title="Redo (Ctrl+Y / Cmd+Shift+Z)">&#8631; Redo</button>
                    </span>
                </div>
                <textarea id="css_content" spellcheck="false"></textarea>
                <p>Syntax check (<span id="lint-file">main.css</span>): <b id="lint-status" class="lint-ok">checking...</b></p>
                <div class="cc-row">
                    <button type="submit" name="action" value="save" id="btn-save"<?php echo $theme === '' ? ' disabled' : ''; ?>
                        title="Saves all files of the theme and stores them together as version v<?php echo (int) $current_num + 1; ?> (date and time)">Save all files (new version)</button>
                    <button type="button" id="btn-discard" title="Returns all files to the last saved version">Discard changes</button>
                    <button type="button" id="btn-preview">Preview</button>
                    <button type="button" id="btn-exit">Exit</button>
                </div>
                <p class="cc-hint">"Save" writes all files of the theme (also the files you did not change) and stores them together as the next version with date and time.
                    "Discard changes" returns all files to the last saved version.</p>
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
                                    <option value="<?php echo $h($t); ?>"<?php echo $is_sel($t, $theme); ?>><?php echo $h($t . ($t === $active ? ' (active)' : '')); ?></option>
                                <?php } ?>
                            </select>
                        </label>
                        <div class="cc-row">
                            <?php if ($theme === $active) { ?>
                                <button type="submit" name="action" value="deactivate">Deactivate</button>
                            <?php } else { ?>
                                <button type="submit" name="action" value="activate">Activate</button>
                            <?php } ?>
                            <button type="submit" name="action" value="delete">Delete theme</button>
                        </div>
                    <?php } else { ?>
                        <p><i>No themes yet. Type a name below and click "Save as new theme".</i></p>
                    <?php } ?>
                    <p>Active on the site: <b><?php echo $h($active_label); ?></b></p>

                    <h4>Add a new theme</h4>
                    <p>Saves all files currently in the editor (all tabs) as a new theme.</p>
                    <input type="text" name="new_name" id="new_name" placeholder="e.g. custom-green" maxlength="40">
                    <div class="cc-row">
                        <button type="submit" name="action" value="saveas" id="btn-saveas">Save as new theme</button>
                    </div>

                    <h4>Theme folder</h4>
                    <p class="cc-path"><code><?php echo $h($theme !== '' ? customcss_theme_dir($theme) : '(none)'); ?></code></p>
                </details>

                <details class="cc-box" data-box="editor"<?php echo $open_box === 'editor' ? ' open' : ''; ?>>
                    <summary><h3>Editor</h3></summary>
                    <p>Shortcuts: <b>Ctrl+S</b> save, <b>Ctrl+Z</b> undo, <b>Ctrl+Y</b> / <b>Ctrl+Shift+Z</b> redo, <b>Ctrl+Space</b> suggestions,
                        <b>Ctrl+/</b> toggle comment, <b>Tab</b> indent (on a Mac: Cmd instead of Ctrl).
                        Each file tab has its own undo history (until the page is reloaded).</p>
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
                    <p>Current version of "<?php echo $h($theme); ?>": <b>v<?php echo (int) $current_num; ?></b></p>
                    <p>Every "Save" stores all files of the theme together as the next version (date and time). The last <?php echo (int) CUSTOMCSS_KEEP_VERSIONS; ?> versions are kept.</p>
                    <?php if ($versions) { ?>
                        <select name="version_name" id="version-select">
                            <?php foreach ($versions as $v) { ?>
                                <option value="<?php echo $h($v); ?>"<?php echo $is_sel($v, $version_sel); ?>><?php echo $h(customcss_version_label($theme, $v)); ?></option>
                            <?php } ?>
                        </select>
                        <div class="cc-row">
                            <button type="button" id="btn-load-version">Load into editor</button>
                            <button type="submit" name="action" value="delete_version">Delete this version</button>
                            <button type="submit" name="action" value="clear_history"<?php echo count($versions) < 2 ? ' disabled' : ''; ?>>Delete all history</button>
                        </div>
                        <p class="cc-hint">"Load into editor" puts all files of the version into the tabs; click "Save" to store them as the newest version.
                            "Delete all history" keeps only the newest version.</p>
                    <?php } else { ?>
                        <p><i>No versions yet.</i></p>
                    <?php } ?>

                    <?php if ($defaults) { ?>
                        <h4>Original themes</h4>
                        <p>Restoring replaces all files of the theme with the original shipped with the plugin (or recreates the theme if it was deleted) and stores it as a new version.</p>
                        <select name="default_theme" id="default-select">
                            <?php foreach ($defaults as $d) { ?>
                                <option value="<?php echo $h($d); ?>"<?php echo $is_sel($d, $theme); ?>><?php echo $h($d); ?></option>
                            <?php } ?>
                        </select>
                        <div class="cc-row">
                            <button type="submit" name="action" value="restore_default" id="btn-restore">Restore original</button>
                        </div>
                    <?php } ?>
                </details>

                <details class="cc-box" data-box="switch"<?php echo $open_box === 'switch' ? ' open' : ''; ?>>
                    <summary><h3>Light / dark mode switch</h3></summary>
                    <p>The active theme loads <code>main.css</code> and the extra files, then <code>light-mode.css</code> or <code>dark-mode.css</code>.</p>
                    <p><label><input type="checkbox" name="sw_enabled" value="1"<?php echo $settings['switch'] ? ' checked' : ''; ?>> Enable the switch</label></p>
                    <p class="cc-hint">When enabled, every page shows a small round button that lets each user switch between light and dark mode. The choice is remembered in the user's browser.</p>
                    <label class="cc-field">
                        <span>Mode for users who have not chosen yet</span>
                        <select name="sw_default">
                            <option value="auto"<?php echo $is_sel('auto', $settings['default_mode']); ?>>Follow the device setting</option>
                            <option value="light"<?php echo $is_sel('light', $settings['default_mode']); ?>>Light</option>
                            <option value="dark"<?php echo $is_sel('dark', $settings['default_mode']); ?>>Dark</option>
                        </select>
                    </label>
                    <p class="cc-hint">While the switch is off, this mode is used for everybody.</p>
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
            <select id="preview-mode" title="Which mode file is used in the preview">
                <option value="light-mode">Light mode</option>
                <option value="dark-mode">Dark mode</option>
            </select>
            <button type="button" id="btn-preview-reload">Reload</button>
            <button type="button" id="btn-preview-close">Close preview</button>
        </p>
        <p class="cc-hint">The preview shows the editor content (main.css, extra files and the selected mode file) instead of the site's theme. Nothing is saved until you click "Save".</p>
        <iframe id="preview-frame"></iframe>
    </div>

    <?php foreach ($cm_js as $f) { ?>
        <script src="<?php echo $cm_url . $f; ?>"<?php echo $nonce; ?>></script>
    <?php } ?>
    <script<?php echo $nonce; ?>>
    (function () {
        var REQUIRED = ['main', 'light-mode', 'dark-mode'];
        var initialFiles = <?php echo json_encode((object) $files, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        var savedFiles = <?php echo json_encode((object) $saved_files, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        var pluginsUrl = <?php echo json_encode($plugins_url); ?>;
        var selfUrl = <?php echo json_encode($self); ?>;
        var baseUrlShort = <?php echo json_encode($baseurl_short); ?>;
        var currentTheme = <?php echo json_encode($theme); ?>;
        var themePath = <?php echo json_encode($theme !== '' ? 'Custom/themes/' . $theme . '/' : ''); ?>;
        var form = document.getElementById('css-form');
        var tabsEl = document.getElementById('cc-tabs');
        var infoEl = document.getElementById('cc-file-info');
        var lintFileEl = document.getElementById('lint-file');
        var previewBox = document.getElementById('preview-box');
        var previewFrame = document.getElementById('preview-frame');
        var previewPath = document.getElementById('preview-path');
        var previewMode = document.getElementById('preview-mode');
        var statusEl = document.getElementById('lint-status');
        var previewTimer = null;
        var leaving = false;

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

        // ---------------------------------------------------------------
        // Files: one CodeMirror document per tab (each keeps its own undo history)
        // ---------------------------------------------------------------
        var files = [];   // [{name, doc}] in loading order
        var current = null;

        function sortFiles() {
            var extras = files.filter(function (f) { return REQUIRED.indexOf(f.name) === -1; })
                .sort(function (a, b) { return a.name < b.name ? -1 : 1; });
            var byName = function (n) { return files.filter(function (f) { return f.name === n; })[0]; };
            files = [byName('main')].concat(extras, [byName('light-mode'), byName('dark-mode')]);
        }
        function findFile(name) {
            return files.filter(function (f) { return f.name === name; })[0] || null;
        }
        function fileDirty(f) {
            return !Object.prototype.hasOwnProperty.call(savedFiles, f.name) || f.doc.getValue() !== savedFiles[f.name];
        }
        function isDirty() {
            if (files.some(fileDirty)) { return true; }
            return Object.keys(savedFiles).some(function (n) { return !findFile(n); }); // removed files
        }
        function role(name) {
            if (name === 'main') { return 'shared by light and dark mode, loaded first'; }
            if (name === 'light-mode') { return 'colours of the light mode, loaded last in light mode'; }
            if (name === 'dark-mode') { return 'colours of the dark mode, loaded last in dark mode'; }
            return 'extra file, loaded after main.css in both modes';
        }

        function renderTabs() {
            tabsEl.innerHTML = '';
            files.forEach(function (f) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'cc-tab';
                b.setAttribute('role', 'tab');
                b.setAttribute('aria-selected', f === current ? 'true' : 'false');
                b.title = f.name + '.css - ' + role(f.name);
                var label = document.createElement('span');
                label.textContent = f.name + '.css';
                b.appendChild(label);
                if (fileDirty(f)) {
                    var dot = document.createElement('span');
                    dot.className = 'cc-dot';
                    dot.textContent = '•';
                    dot.title = 'Changed, not saved';
                    b.appendChild(dot);
                }
                if (REQUIRED.indexOf(f.name) === -1) {
                    var x = document.createElement('span');
                    x.className = 'cc-x';
                    x.textContent = '×';
                    x.title = 'Remove this file from the theme';
                    x.addEventListener('click', function (e) { e.stopPropagation(); removeFile(f); });
                    b.appendChild(x);
                }
                b.addEventListener('click', function () { selectFile(f); });
                tabsEl.appendChild(b);
            });
            var add = document.createElement('button');
            add.type = 'button';
            add.className = 'cc-tab-add';
            add.textContent = '+ Add CSS file';
            add.title = 'Add another CSS file to the theme (loaded after main.css in both modes)';
            add.addEventListener('click', addFile);
            tabsEl.appendChild(add);
        }

        function selectFile(f) {
            current = f;
            editor.swapDoc(f.doc);
            infoEl.textContent = (themePath || '') + f.name + '.css – ' + role(f.name);
            lintFileEl.textContent = f.name + '.css';
            if (f.name === 'light-mode' || f.name === 'dark-mode') { previewMode.value = f.name; }
            if (editor.performLint) { editor.performLint(); } else { updateStatus(lintCss(editor.getValue())); }
            renderTabs();
            editor.focus();
        }

        function addFile() {
            var raw = window.prompt('Name of the new CSS file (letters, numbers, "-" and "_"; ".css" is added):', '');
            if (raw === null) { return; }
            var name = String(raw).toLowerCase().replace(/\.css$/, '').replace(/[^a-z0-9_-]+/g, '-').replace(/^[-_]+|[-_]+$/g, '').slice(0, 40);
            if (!name) { return; }
            if (findFile(name)) { alert('The file ' + name + '.css already exists in this theme.'); return; }
            var f = { name: name, doc: CodeMirror.Doc('/* ' + name + '.css - ' + role(name) + ' */\n', 'css') };
            files.push(f);
            sortFiles();
            selectFile(f);
        }

        function removeFile(f) {
            if (!confirm('Remove ' + f.name + '.css from the theme? (It is deleted when you click "Save"; "Discard changes" brings it back.)')) { return; }
            files = files.filter(function (x) { return x !== f; });
            if (current === f) { selectFile(findFile('main')); } else { renderTabs(); }
        }

        // Back to the last saved version (undo still works inside each file)
        function discardChanges() {
            if (!isDirty()) { return; }
            if (!confirm('Discard all changes that are not saved? All files return to the last saved version.')) { return; }
            var keep = [];
            Object.keys(savedFiles).forEach(function (n) {
                var f = findFile(n);
                if (f) {
                    if (f.doc.getValue() !== savedFiles[n]) { f.doc.setValue(savedFiles[n]); }
                } else {
                    f = { name: n, doc: CodeMirror.Doc(savedFiles[n], 'css') };
                }
                keep.push(f);
            });
            files = keep;
            sortFiles();
            selectFile(findFile(current && findFile(current.name) ? current.name : 'main'));
        }

        function collectFiles() {
            var out = {};
            files.forEach(function (f) { out[f.name] = f.doc.getValue(); });
            return out;
        }

        // ---------------------------------------------------------------
        // Editor
        // ---------------------------------------------------------------
        function lintForEditor(text) {
            var list = lintCss(text);
            updateStatus(list);
            return list;
        }
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

        Object.keys(initialFiles).forEach(function (n) {
            files.push({ name: n, doc: CodeMirror.Doc(initialFiles[n], 'css') });
        });
        REQUIRED.forEach(function (n) {
            if (!findFile(n)) { files.push({ name: n, doc: CodeMirror.Doc('', 'css') }); }
        });
        sortFiles();
        selectFile(findFile('main'));

        document.getElementById('btn-undo').addEventListener('click', function () { editor.undo(); editor.focus(); });
        document.getElementById('btn-redo').addEventListener('click', function () { editor.redo(); editor.focus(); });
        document.getElementById('btn-discard').addEventListener('click', discardChanges);

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
        // Preview: main.css + extra files + the selected mode file, without saving.
        // The site's theme links are switched off inside the frame. Uses constructable
        // stylesheets (not blocked by CSP) with a <style> fallback.
        // ---------------------------------------------------------------
        function previewCss() {
            var mode = previewMode.value;
            return files.filter(function (f) {
                return f.name !== 'light-mode' && f.name !== 'dark-mode';
            }).concat([findFile(mode)]).map(function (f) {
                return '/* ' + f.name + '.css */\n' + f.doc.getValue();
            }).join('\n');
        }
        function injectCss() {
            try {
                var doc = previewFrame.contentDocument;
                var win = previewFrame.contentWindow;
                if (!doc || !win || !doc.head) { return; }
                var css = previewCss();
                [].forEach.call(doc.querySelectorAll('link[rel="stylesheet"][href*="Custom/themes/"]'), function (l) {
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
        previewFrame.addEventListener('load', injectCss);
        previewMode.addEventListener('change', injectCss);
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

        // Pressing Enter in the "new theme" field must only trigger "Save as new theme"
        document.getElementById('new_name').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btn-saveas').click(); }
        });

        // ---------------------------------------------------------------
        // Change tracking, theme switching, version loading, confirmations
        // ---------------------------------------------------------------
        var tabsTimer = null;
        editor.on('change', function () {
            clearTimeout(tabsTimer);
            tabsTimer = setTimeout(renderTabs, 250); // dirty markers
            if (!previewBox.hidden) {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(injectCss, 300);
            }
        });

        var select = document.getElementById('theme-select');
        if (select) {
            select.addEventListener('change', function () {
                if (isDirty() && !confirm('You have unsaved changes. Switch theme without saving?')) {
                    select.value = currentTheme;
                    return;
                }
                leaving = true;
                window.location.href = selfUrl + '?theme=' + encodeURIComponent(select.value);
            });
        }

        var loadBtn = document.getElementById('btn-load-version');
        if (loadBtn) {
            loadBtn.addEventListener('click', function () {
                var v = document.getElementById('version-select').value;
                if (isDirty() && !confirm('You have unsaved changes. Replace the editor content with the selected version?')) { return; }
                leaving = true;
                window.location.href = selfUrl + '?theme=' + encodeURIComponent(currentTheme) + '&version=' + encodeURIComponent(v);
            });
        }

        form.addEventListener('submit', function (e) {
            var act = (e.submitter && e.submitter.value) || '';
            if (act === 'save' || act === 'saveas') {
                var errors = 0, bad = [];
                files.forEach(function (f) {
                    var r = countProblems(lintCss(f.doc.getValue()));
                    if (r.errors) { errors += r.errors; bad.push(f.name + '.css'); }
                });
                if (errors && !confirm('Syntax errors: ' + errors + ' (in ' + bad.join(', ') + '). Save anyway?')) {
                    e.preventDefault();
                    return;
                }
            }
            if (act === 'saveas' && !document.getElementById('new_name').value.trim()) {
                alert('Enter a name for the new theme.');
                e.preventDefault();
                return;
            }
            if (act === 'delete' && !confirm('Delete the theme "' + currentTheme + '" (all its files)? Its version history is kept.')) {
                e.preventDefault();
                return;
            }
            if (act === 'delete_version') {
                var opt = document.getElementById('version-select').selectedOptions[0];
                if (!opt || !confirm('Delete the version "' + opt.textContent + '"? This cannot be undone.')) {
                    e.preventDefault();
                    return;
                }
            }
            if (act === 'clear_history' && !confirm('Delete the whole version history of "' + currentTheme + '" except the newest version? This cannot be undone.')) {
                e.preventDefault();
                return;
            }
            if (act === 'restore_default') {
                var d = document.getElementById('default-select').value;
                if (!confirm('Restore the original theme "' + d + '"? All its files are replaced; the current files stay in the version history.'
                    + (isDirty() ? ' Unsaved editor changes are lost.' : ''))) {
                    e.preventDefault();
                    return;
                }
            }
            document.getElementById('files-json').value = JSON.stringify(collectFiles());
            document.getElementById('editor-dirty').value = isDirty() ? '1' : '0';
            leaving = true;
        });

        document.getElementById('btn-exit').addEventListener('click', function () {
            if (isDirty() && !confirm('You have unsaved changes. Exit without saving?')) { return; }
            leaving = true;
            window.location.href = pluginsUrl;
        });
        window.addEventListener('beforeunload', function (e) {
            if (!leaving && isDirty()) { e.preventDefault(); e.returnValue = ''; }
        });
    })();
    </script>
</div>
<?php
include '../../../include/footer.php';
