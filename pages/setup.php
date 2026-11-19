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
    $notes   = array_merge(customcss_relocate(), customcss_migrate());
    $created = customcss_install_defaults();
    if ($created) {
        $notes[] = 'Original themes created: ' . implode(', ', $created) . '.';
    }
    foreach (customcss_repair_empty_main() as $r) {
        $notes[] = 'main.css of the theme "' . $r . '" was empty after the conversion of older theme files - the original main.css'
            . ' (login page, banner, shapes) was added as a new version. If the login button still looks old, open light-mode.css /'
            . ' dark-mode.css and use "Load original" (old login rules may be in these files).';
    }
}

$theme       = customcss_slug((string) ($_POST['theme'] ?? $_GET['theme'] ?? ''));
$action      = (string) ($_POST['action'] ?? '');
$posted      = null;  // files sent by the editor (name => CSS)
$keep_posted = false; // show the sent files again (not saved)
$import      = null;  // uploaded archive waiting for "which file is which" (since 1.22)

// ---- Download as .zip: ?download=<theme> or ?download_all=1 (since 1.22) ----
if ($error === '' && (isset($_GET['download']) || isset($_GET['download_all']))) {
    $list = isset($_GET['download_all']) ? customcss_list() : array(customcss_slug((string) $_GET['download']));
    $list = array_values(array_filter($list, 'customcss_theme_exists'));
    if (!customcss_zip_available()) {
        $error = 'The PHP zip extension is missing (install php-zip and restart PHP-FPM) - themes cannot be downloaded.';
    } elseif (!$list) {
        $error = 'There is no theme to download.';
    } else {
        $zipfile = customcss_export_zip($list);
        if ($zipfile === '') {
            $error = 'The archive could not be created (check the temporary folder of ResourceSpace).';
        } else {
            customcss_send_zip($zipfile, (isset($_GET['download_all']) ? 'customcss-themes' : $list[0]) . '-' . date('Ymd-His') . '.zip');
            exit;
        }
    }
}

// ---- Import a theme from a .zip archive (since 1.22) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '' && in_array($action, array('import', 'import_confirm', 'import_cancel'), true)) {
    $existing = customcss_list();
    if ($action === 'import') {
        $up = $_FILES['theme_zip'] ?? null;
        if (!is_array($up) || (int) ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $up['tmp_name'])) {
            $error = (int) ($up['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
                ? 'Choose a .zip file to upload.'
                : 'The upload failed (check upload_max_filesize / post_max_size of PHP).';
        } else {
            $ierr = '';
            $data = customcss_import_read((string) $up['tmp_name'], (string) $up['name'], $ierr);
            if ($data === null) {
                $error = $ierr;
            } else {
                $name          = customcss_slug((string) ($_POST['import_name'] ?? ''));
                $data['theme'] = $name !== '' ? $name : $data['theme'];
                $data['zip']   = basename((string) $up['name']);
                $complete      = !array_diff(CUSTOMCSS_REQUIRED, array_keys($data['files']));
                if ($complete && !in_array($data['theme'], $existing, true)) {
                    // All three files with the right names and a new theme name: import directly
                    $v = customcss_save_theme($data['theme'], $data['files']);
                    if ($v === false) {
                        $error = 'Cannot create the theme (check web server permissions for ' . customcss_themes_dir() . ').';
                    } else {
                        $theme   = $data['theme'];
                        $message = 'Theme "' . $theme . '" imported from ' . $data['zip'] . ' (' . count($data['files']) . ' files, version '
                            . customcss_version_label($theme, $v) . '). Activate it to use it on the site.';
                    }
                } else {
                    $token = customcss_import_save($data);
                    if ($token === '') {
                        $error = 'The upload could not be stored (check permissions of ' . customcss_dir() . ').';
                    } else {
                        $import = $data + array('token' => $token, 'map' => customcss_import_guess(array_keys($data['files'])));
                    }
                }
            }
        }
    } else {
        $token = (string) ($_POST['import_token'] ?? '');
        $data  = customcss_import_load($token);
        if ($action === 'import_cancel') {
            customcss_import_drop($token);
            $message = 'Import cancelled.';
        } elseif ($data === null) {
            $error = 'The uploaded archive is no longer available - upload it again.';
        } else {
            $map = array();
            foreach (CUSTOMCSS_REQUIRED as $role) {
                $src        = (string) ($_POST['map'][$role] ?? '');
                $map[$role] = isset($data['files'][$src]) ? $src : '';
            }
            $name    = customcss_slug((string) ($_POST['import_name'] ?? ''));
            $replace = !empty($_POST['import_replace']);
            $files   = customcss_import_files($data['files'], $map);
            $used    = array_filter($map);
            if ($name === '') {
                $error = 'Enter a theme name (letters, numbers, "-" and "_").';
            } elseif (count($used) !== count(array_unique($used))) {
                $error = 'Each archive file can be used for only one of main.css, light-mode.css and dark-mode.css.';
            } elseif (in_array($name, $existing, true) && !$replace) {
                $error = 'A theme "' . $name . '" already exists - choose another name or tick "Replace the existing theme".';
            } elseif (count($files) > 30) {
                $error = 'A theme can have at most 30 files.';
            } else {
                $v = customcss_save_theme($name, $files);
                if ($v === false) {
                    $error = 'Cannot save the theme (check web server permissions for ' . customcss_themes_dir() . ').';
                } else {
                    customcss_import_drop($token);
                    $theme   = $name;
                    $message = 'Theme "' . $name . '" ' . (in_array($name, $existing, true) ? 'replaced' : 'imported') . ' from ' . ($data['zip'] ?? 'the archive')
                        . ' (' . count($files) . ' files, version ' . customcss_version_label($name, $v) . ').';
                }
            }
            if ($error !== '') {
                $data['theme'] = $name !== '' ? $name : $data['theme'];
                $import        = $data + array('token' => $token, 'map' => $map);
            }
        }
    }
}

// ---- New theme (since 1.25), configurator of a theme (since 1.28), duplicate (since 1.28) ----
// The panel "Create a new theme" / "Configurator" replaces the editor while it is open.
$panel_mode = '';  // '' | 'create' | 'configure' - panel shown by the server (configurator link or after an error)
$repost     = false; // show the values that were sent again (after an error)
$cfg_theme  = '';
$cfg_ref    = '';
if ($error === '' && isset($_GET['configure'])) {
    $cfg_theme  = customcss_slug((string) $_GET['configure']);
    $cfg_ref    = (string) ($_GET['ref'] ?? '');
    $panel_mode = customcss_theme_exists($cfg_theme) ? 'configure' : '';
    if ($panel_mode === '') {
        $error = 'The theme "' . $cfg_theme . '" does not exist.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '' && in_array($action, array('create_new', 'configure_save', 'duplicate'), true) && enforcePostRequest(false)) {
    $existing = customcss_list();
    if ($action === 'duplicate') {
        $src  = customcss_slug((string) ($_POST['dup_theme'] ?? ''));
        $name = customcss_slug((string) ($_POST['dup_name'] ?? ''));
        if (!in_array($src, $existing, true)) {
            $error = 'Choose the theme to duplicate.';
        } elseif ($name === '') {
            $error = 'Enter a name for the copy (letters, numbers, "-" and "_").';
        } elseif (in_array($name, $existing, true)) {
            $error = 'A theme "' . $name . '" already exists - choose another name.';
        } else {
            $v = customcss_save_theme($name, customcss_read_theme($src));
            if ($v === false) {
                $error = 'Cannot create the theme (check web server permissions for ' . customcss_themes_dir() . ').';
            } else {
                $theme   = $name;
                $message = 'Theme "' . $src . '" duplicated as "' . $name . '" (saved files, version ' . customcss_version_label($name, $v) . ').';
            }
        }
    } elseif ($action === 'create_new') {
        $name  = customcss_slug((string) ($_POST['new_theme_name'] ?? ''));
        $start = (string) ($_POST['new_start'] ?? 'blank');
        $tpl   = (string) ($_POST['new_template'] ?? '');
        $files = null;
        $changed = 0;
        if ($name === '') {
            $error = 'Enter a name for the new theme (letters, numbers, "-" and "_").';
        } elseif (in_array($name, $existing, true)) {
            $error = 'A theme "' . $name . '" already exists - choose another name.';
        } elseif ($start === 'blank') {
            $files = customcss_blank_files($name);
        } elseif (($files = customcss_template_files($tpl)) === null) {
            $error = 'Choose a template.';
        } else {
            $res = customcss_config_apply($files, null, (array) ($_POST['var'][$tpl] ?? array()), array(), (array) ($_POST['sw'][$tpl] ?? array()));
            $files = $res['files'];
            $changed = $res['changed'];
            if ($res['bad']) {
                $error = 'These values are not allowed (no ; { } < > or comments; \\ only before a hex code, e.g. \\2192): ' . implode(', ', $res['bad']) . '.';
            }
            // the theme name in the file headers
            $from = strtoupper(str_replace(array('-', '_'), ' ', substr($tpl, strpos($tpl, ':') + 1)));
            $to   = strtoupper(str_replace(array('-', '_'), ' ', $name));
            foreach ($files as $fn => $css) {
                $files[$fn] = str_replace($from, $to, substr($css, 0, 600)) . substr($css, 600);
            }
        }
        if ($error === '' && $files !== null) {
            $v = customcss_save_theme($name, $files);
            if ($v === false) {
                $error = 'Cannot create the theme (check web server permissions for ' . customcss_themes_dir() . ').';
            } else {
                $theme   = $name;
                $message = 'Theme "' . $name . '" created ' . ($start === 'blank' ? 'with empty files'
                    : 'from the template ' . substr($tpl, strpos($tpl, ':') + 1) . ' (' . $changed . ' value(s) changed)')
                    . ', version ' . customcss_version_label($name, $v) . '. Edit it below, preview it and activate it to use it on the site.';
            }
        }
        if ($error !== '') {
            $panel_mode = 'create';
            $repost     = true;
        }
    } else {
        // configurator: change values, add missing variables, switches
        $cfg_theme = customcss_slug((string) ($_POST['conf_theme'] ?? ''));
        $cfg_ref   = (string) ($_POST['conf_ref'] ?? '');
        if (!in_array($cfg_theme, $existing, true)) {
            $error = 'The theme "' . $cfg_theme . '" does not exist.';
        } else {
            $files = customcss_read_theme($cfg_theme);
            $ref   = $cfg_ref !== '' ? customcss_template_files($cfg_ref) : null;
            $res   = customcss_config_apply($files, $ref, (array) ($_POST['var']['cfg'] ?? array()), (array) ($_POST['add']['cfg'] ?? array()),
                (array) ($_POST['sw']['cfg'] ?? array()), (array) ($_POST['rules']['cfg'] ?? array()));
            if ($res['bad']) {
                $error = 'These values are not allowed (no ; { } < > or comments; \\ only before a hex code, e.g. \\2192): ' . implode(', ', $res['bad']) . '.';
            } elseif ($res['changed'] + $res['added'] === 0) {
                $theme   = $cfg_theme;
                $message = 'Configurator: nothing was changed in "' . $cfg_theme . '".';
            } else {
                $v = customcss_save_theme($cfg_theme, $res['files']);
                if ($v === false) {
                    $error = 'Cannot save the theme (check web server permissions for ' . customcss_themes_dir() . ').';
                } else {
                    $theme   = $cfg_theme;
                    $message = 'Theme "' . $cfg_theme . '" saved by the configurator: ' . $res['changed'] . ' value(s) changed, ' . $res['added']
                        . ' variable(s) added (version ' . customcss_version_label($cfg_theme, $v) . ').'
                        . ($cfg_theme === customcss_active() ? ' The theme is active - the change is visible on the site.' : '');
                }
            }
        }
        if ($error !== '') {
            $panel_mode = in_array($cfg_theme, $existing, true) ? 'configure' : '';
            $repost     = true;
        }
    }
}

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
    } elseif ($action === 'delete_file') {
        // Deletes one extra file of the theme right away and stores the rest as a new version (since 1.22)
        $fname = customcss_slug((string) ($_POST['file_name'] ?? ''));
        $saved = in_array($theme, $existing, true) ? customcss_read_theme($theme) : array();
        if (!$saved) {
            $error = 'No theme selected.';
        } elseif ($fname === '' || in_array($fname, CUSTOMCSS_REQUIRED, true)) {
            $error = 'main.css, light-mode.css and dark-mode.css cannot be deleted.';
        } elseif (!array_key_exists($fname, $saved)) {
            $error = 'The file ' . $fname . '.css is not part of the saved theme.';
        } else {
            unset($saved[$fname]);
            $v = customcss_save_theme($theme, $saved);
            $message = $v === false ? '' : 'File ' . $fname . '.css deleted from "' . $theme . '" (saved as version ' . customcss_version_label($theme, $v) . '; the older versions still contain it).';
            $error   = $v === false ? 'Could not delete the file (check web server permissions for ' . customcss_themes_dir() . ').' : '';
        }
        if ($posted !== null) {
            unset($posted[$fname]);
        }
        $keep_posted = $dirty && $posted !== null;
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
    } elseif ($action === 'reset') {
        if (trim((string) ($_POST['reset_confirm'] ?? '')) !== 'RESET') {
            $error = 'Not reset: type RESET (capital letters) to confirm.';
        } elseif (!customcss_reset()) {
            $error = 'The plugin could not be reset (check permissions of ' . customcss_base_dir() . ').';
        } else {
            redirect($self . '?done=reset');
            exit;
        }
        $open_box = 'reset';
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
if (($_GET['done'] ?? '') === 'reset') {
    $message = 'The plugin was reset: all themes, versions and settings were deleted. The original themes were created again - activate a theme to use it.';
}
$saved_files = $theme !== '' ? customcss_read_theme($theme) : array('main' => '', 'light-mode' => '', 'dark-mode' => '');
// Original files of this theme (for the "Load original" button), empty when the theme has no original
$orig_files = in_array($theme, customcss_defaults(), true) ? customcss_read_files(customcss_defaults_dir() . '/' . $theme) : array();

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
        .cc-tab-cfg { padding: 6px 12px; margin: 0 0 4px 6px; min-height: 0; }
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
        /* Outline buttons (Add CSS file, Load original, Delete file, Undo, Redo, downloads) - since 1.22 */
        .cc-page button.cc-outline, .cc-page a.cc-outline { display: inline-flex; align-items: center; gap: 5px; background: transparent; color: inherit;
            border: 1px solid currentColor; border-radius: 4px; box-shadow: none; text-decoration: none; padding: 3px 10px; min-height: 0; font: inherit; cursor: pointer; }
        .cc-page button.cc-outline:hover, .cc-page button.cc-outline:focus, .cc-page a.cc-outline:hover, .cc-page a.cc-outline:focus { background: rgba(128, 128, 128, 0.18); }
        .cc-page button.cc-outline:disabled { opacity: 0.45; cursor: default; }
        .cc-page .cc-outline svg { fill: currentColor; flex: 0 0 auto; }
        .cc-page button.cc-danger:hover { color: #c62828; }
        .cc-import { border-color: #b26a00; }
        .cc-import table { border-collapse: collapse; margin: 6px 0; }
        .cc-import th, .cc-import td { text-align: left; padding: 4px 10px 4px 0; }
        .cc-import select, .cc-import input[type=text] { min-width: 240px; max-width: 100%; }
        .cc-side input[type=file] { max-width: 100%; }
        #lint-status.lint-ok { color: #2e7d32; }
        #lint-status.lint-warn { color: #b26a00; }
        #lint-status.lint-bad { color: #c62828; }
        /* New theme wizard (since 1.25) */
        .cc-new { margin: 12px 0; }
        .cc-new-top { display: flex; flex-wrap: wrap; gap: 12px 32px; align-items: flex-start; }
        .cc-new-top .cc-field input { min-width: 260px; }
        .cc-start { border: 0; padding: 0; margin: 0; }
        .cc-start legend { font-weight: bold; margin-bottom: 4px; padding: 0; }
        .cc-start label { display: block; margin: 4px 0; }
        .cc-new h4 { margin: 16px 0 6px; }
        .cc-main-secs { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 8px 16px; }
        .cc-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; align-items: start; }
        .cc-cols > h4 { margin-bottom: 0; }
        @media (max-width: 900px) { .cc-cols, .cc-main-secs { grid-template-columns: 1fr; } }
        .cc-sec { border: 1px solid rgba(128, 128, 128, 0.4); border-radius: 6px; padding: 6px 10px; }
        .cc-sec > summary { cursor: pointer; font-weight: bold; }
        .cc-count { font-weight: normal; opacity: 0.7; }
        .cc-vars { margin-top: 6px; }
        .cc-group { margin: 10px 0 4px; font-size: 0.95em; opacity: 0.85; }
        .cc-var { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, 46%); gap: 8px; align-items: center; margin: 4px 0; }
        .cc-var-label { font-size: 0.9em; line-height: 1.25; }
        .cc-var-label code { display: block; opacity: 0.65; font-size: 0.85em; }
        .cc-var-input { display: flex; gap: 6px; align-items: center; }
        .cc-var-input input[type=text], .cc-var-input select { width: 100%; min-width: 0; box-sizing: border-box; }
        .cc-var-missing { background: rgba(255, 170, 0, 0.12); border-radius: 4px; padding: 2px 4px; }
        .cc-missing { display: block; color: #b45309; font-size: 0.85em; font-weight: bold; }
        .cc-count .cc-missing { display: inline; }
        .cc-add { display: block; font-size: 0.85em; margin-top: 2px; }
        .cc-missing-info { margin: 6px 0; }
        .cc-new-buttons { margin-top: 16px; }
        .cc-new > form > h3 { margin-top: 0; }
        .cc-var-input input.cc-color { width: 40px; min-width: 40px; height: 30px; padding: 0 2px; cursor: pointer; }
    </style>

    <?php if ($import !== null) {
        $inames = array_keys($import['files']);
        $roles  = array('main' => 'main.css (shared, loaded first)', 'light-mode' => 'light-mode.css (light mode colours)', 'dark-mode' => 'dark-mode.css (dark mode colours)'); ?>
        <!-- Import: which archive file is which (since 1.22) -->
        <div class="cc-box cc-import">
            <h3>Import theme from <?php echo $h($import['zip'] ?? 'the archive'); ?></h3>
            <p>The archive contains <?php echo count($inames); ?> CSS file(s): <code><?php echo $h(implode('.css, ', $inames) . '.css'); ?></code>.
                Choose which file is used for each required file of the theme, or let the plugin create an empty one. All other files are added as extra files with their own names.</p>
            <form method="post" action="<?php echo $h($self); ?>" id="cc-import-map">
                <?php generateFormToken('customcss_import'); ?>
                <input type="hidden" name="import_token" value="<?php echo $h($import['token']); ?>">
                <table>
                    <tr><th>Theme name</th><td><input type="text" name="import_name" maxlength="40" value="<?php echo $h($import['theme']); ?>"></td></tr>
                    <?php foreach ($roles as $role => $label) { ?>
                        <tr><th><?php echo $h($label); ?></th><td>
                            <select name="map[<?php echo $h($role); ?>]">
                                <option value="">(create an empty file)</option>
                                <?php foreach ($inames as $n) { ?><option value="<?php echo $h($n); ?>"<?php echo ($import['map'][$role] ?? '') === $n ? ' selected' : ''; ?>><?php echo $h($n . '.css'); ?></option><?php } ?>
                            </select>
                        </td></tr>
                    <?php } ?>
                </table>
                <?php if (in_array($import['theme'], $themes ?? customcss_list(), true)) { ?>
                    <p><label><input type="checkbox" name="import_replace" value="1"> Replace the existing theme "<?php echo $h($import['theme']); ?>" (its current files stay in the version history)</label></p>
                <?php } ?>
                <div class="cc-row">
                    <button type="submit" name="action" value="import_confirm">Import theme</button>
                    <button type="submit" name="action" value="import_cancel">Cancel</button>
                </div>
            </form>
        </div>
    <?php } ?>

    <?php
    // ---- Panel "Create a new theme" (since 1.25) / "Configurator" (since 1.28) ----
    $tpl_list  = customcss_templates();
    $originals = array_filter($tpl_list, function ($k) { return strpos($k, 'default:') === 0; }, ARRAY_FILTER_USE_KEY);
    $tpl_sel   = (string) ($_POST['new_template'] ?? (isset($tpl_list['default:custom-blue']) ? 'default:custom-blue' : (string) array_key_first($tpl_list)));
    $start_sel = (string) ($_POST['new_start'] ?? 'template');
    $fonts     = customcss_fonts();
    if ($panel_mode === 'configure' && !isset($_GET['ref']) && !$repost) {
        $cfg_ref = isset($originals['default:custom-blue']) ? 'default:custom-blue' : (string) array_key_first($originals);
    }
    $field = function ($key, $role, array $var) use ($h, $repost, $fonts) {
        $posted  = $repost ? ($_POST['var'][$key][$role][$var['name']] ?? null) : null;
        $value   = $posted !== null ? (string) $posted : $var['value'];
        $kind    = customcss_var_kind($var['name'], $var['value']);
        $fname   = 'var[' . $key . '][' . $role . '][' . $var['name'] . ']';
        $label   = $var['help'] !== '' ? $var['help'] : $var['name'];
        $missing = !empty($var['missing']);
        $out = '<label class="cc-var' . ($missing ? ' cc-var-missing' : '') . '"><span class="cc-var-label">' . $h($label) . ' <code>' . $h($var['name']) . '</code>';
        if ($missing) {
            $add = $repost && !empty($_POST['add'][$key][$role][$var['name']]);
            $out .= '<span class="cc-missing">missing in this theme'
                . (!empty($var['unused']) ? ' – no rule of the theme uses it yet (Load original adds the rules)' : '')
                . (!empty($var['links']) ? ' – starts with the current colour of ' . implode(', ', $var['links']) . '; added, they follow this value' : '') . '</span>'
                . '<span class="cc-add"><input type="checkbox" class="cc-add-box" name="' . $h('add[' . $key . '][' . $role . '][' . $var['name'] . ']') . '" value="1"' . ($add ? ' checked' : '') . '> add to the theme</span>';
        }
        $out .= '</span><span class="cc-var-input">';
        if ($kind === 'color') {
            $hex = strtolower(trim($value));
            if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $hex, $m)) {
                $hex = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
            }
            $out .= '<input type="color" class="cc-color" value="' . $h(preg_match('/^#[0-9a-f]{6}$/', $hex) ? $hex : '#000000') . '" aria-label="' . $h($var['name']) . ' colour">'
                . '<input type="text" class="cc-color-text" name="' . $h($fname) . '" value="' . $h($value) . '" spellcheck="false" maxlength="300">';
        } elseif (in_array($kind, array('font', 'weight', 'transform', 'toggle'), true)) {
            $opts = $kind === 'toggle' ? array('0' => '0 – from ResourceSpace', '1' => '1 – own (next field)')
                : ($kind === 'font' ? $fonts
                : ($kind === 'weight' ? array('300' => '300 light', '400' => '400 normal', '500' => '500 medium', '600' => '600 semi-bold', '700' => '700 bold', '800' => '800 extra bold')
                : array('none' => 'none', 'uppercase' => 'UPPERCASE', 'capitalize' => 'Capitalize', 'lowercase' => 'lowercase')));
            if (!isset($opts[$value])) {
                $opts = array($value => $value . ' (current)') + $opts;
            }
            $out .= '<select name="' . $h($fname) . '">';
            foreach ($opts as $ov => $ol) {
                $out .= '<option value="' . $h($ov) . '"' . ((string) $ov === $value ? ' selected' : '') . '>' . $h($ol) . '</option>';
            }
            $out .= '</select>';
        } else {
            $out .= '<input type="text" name="' . $h($fname) . '" value="' . $h($value) . '" spellcheck="false" maxlength="300">';
        }
        return $out . '</span></label>';
    };
    $section = function ($key, $role, array $sec, $open) use ($h, $field, $repost) {
        $missing = count(array_filter($sec['vars'], function ($v) { return !empty($v['missing']); }));
        $count = count($sec['vars']) + count($sec['switches'] ?? array());
        $out = '<details class="cc-sec"' . ($open || $missing ? ' open' : '') . '><summary>' . $h($sec['title']) . ' <span class="cc-count">(' . $count
            . ($missing ? ', <span class="cc-missing">' . $missing . ' missing</span>' : '') . ')</span></summary><div class="cc-vars">';
        // switches of the file that belong to this section, e.g. "Hide the local login fields" (since 1.27)
        if (!empty($sec['switches'])) {
            $out .= '<h5 class="cc-group">' . $h(preg_match('/(\d+\.\d+\s+[^:]+):[^.]*switch/i', $sec['note'] ?? '', $m) ? $m[1] : 'Switches') . '</h5>';
        }
        foreach ($sec['switches'] ?? array() as $sw) {
            if (!empty($sw['missing'])) {
                $out .= '<div class="cc-var cc-var-missing"><span class="cc-var-label">' . $h($sw['title']) . ' <code>switch ' . $h($sw['id']) . '</code>'
                    . '<span class="cc-missing">missing in this theme – the rules are not in its files (take them with Load original)</span></span><span class="cc-var-input"></span></div>';
                continue;
            }
            $on = $repost ? !empty($_POST['sw'][$key][$role][$sw['id']]) : $sw['on'];
            $out .= '<label class="cc-var"><span class="cc-var-label">' . $h($sw['title']) . ' <code>switch ' . $h($sw['id']) . '</code></span>'
                . '<span class="cc-var-input"><input type="checkbox" name="' . $h('sw[' . $key . '][' . $role . '][' . $sw['id'] . ']') . '" value="1"' . ($on ? ' checked' : '') . '> on</span></label>';
        }
        $group = null;
        foreach ($sec['vars'] as $var) {
            if ($var['group'] !== '' && $var['group'] !== $group) {
                $out .= '<h5 class="cc-group">' . $h($var['group']) . '</h5>';
            }
            $group = $var['group'];
            $out  .= $field($key, $role, $var);
        }
        return $out . '</div></details>';
    };
    // main.css on top, light-mode.css | dark-mode.css side by side (sections paired by their number)
    $render = function ($key, array $model) use ($section) {
        $out = '';
        if (!empty($model['main'])) {
            $out .= '<h4>main.css – shared by the light and dark mode</h4><div class="cc-main-secs">';
            foreach ($model['main'] as $i => $sec) {
                $out .= $section($key, 'main', $sec, $i < 2);
            }
            $out .= '</div>';
        }
        $rows = array();
        foreach (array('light-mode', 'dark-mode') as $role) {
            foreach ($model[$role] ?? array() as $sec) {
                $num = preg_match('/^(\d+)\./', $sec['title'], $m) ? (int) $m[1] : 1000 + count($rows);
                $rows[$num][$role] = $sec;
            }
        }
        ksort($rows);
        if ($rows) {
            $out .= '<div class="cc-cols"><h4>light-mode.css – light mode</h4><h4>dark-mode.css – dark mode</h4>';
            $i = 0;
            foreach ($rows as $pair) {
                $out .= '<div>' . (isset($pair['light-mode']) ? $section($key, 'light-mode', $pair['light-mode'], $i < 2) : '') . '</div>'
                    . '<div>' . (isset($pair['dark-mode']) ? $section($key, 'dark-mode', $pair['dark-mode'], $i < 2) : '') . '</div>';
                $i++;
            }
            $out .= '</div>';
        }
        return $out;
    };
    ?>
    <div class="cc-box cc-new" id="cc-new" data-mode="<?php echo $h($panel_mode !== '' ? $panel_mode : 'create'); ?>"<?php echo $panel_mode !== '' ? '' : ' hidden'; ?>>
        <form method="post" action="<?php echo $h($self); ?>" id="cc-new-form">
            <?php generateFormToken('customcss_new'); ?>
            <?php if ($panel_mode === 'configure') {
                $cfiles = customcss_read_theme($cfg_theme);
                $cref   = $cfg_ref !== '' ? customcss_template_files($cfg_ref) : null;
                $cmodel = customcss_config_model($cfiles, $cref);
                $cmiss  = 0;
                $norule = array(); // files whose rules (PART B) do not use some missing variables / lack switches
                foreach ($cmodel as $crole => $secs) {
                    foreach ($secs as $sec) {
                        foreach ($sec['vars'] as $v) {
                            $cmiss += empty($v['missing']) ? 0 : 1;
                            if (!empty($v['missing']) && !empty($v['unused'])) {
                                $norule[$crole] = true;
                            }
                        }
                        foreach ($sec['switches'] ?? array() as $sw) {
                            if (!empty($sw['missing'])) {
                                $norule[$crole] = true;
                            }
                        }
                    }
                }
                // rules (PART B) that differ from the original, e.g. after an update of the plugin (since 1.31)
                $differs = array();
                foreach (CUSTOMCSS_REQUIRED as $crole) {
                    if (is_array($cref) && trim((string) ($cref[$crole] ?? '')) !== '' && isset($cfiles[$crole])) {
                        $taken = customcss_take_rules($cfiles[$crole], $cref[$crole]);
                        foreach (customcss_parse_switches($cfiles[$crole]) as $sw) {
                            $taken = customcss_apply_switch($taken, $sw['id'], $sw['on']); // switch states are not a difference
                        }
                        if ($taken !== $cfiles[$crole]) {
                            $differs[$crole] = true;
                        }
                    }
                } ?>
                <h3>Configurator: <?php echo $h($cfg_theme); ?></h3>
                <input type="hidden" name="conf_theme" value="<?php echo $h($cfg_theme); ?>">
                <div class="cc-new-top">
                    <label class="cc-field"><span>Compare with (variables that the theme does not have are marked "missing")</span>
                        <select name="conf_ref" id="cc-conf-ref" data-theme="<?php echo $h($cfg_theme); ?>">
                            <?php foreach ($originals as $k => $l) { ?><option value="<?php echo $h($k); ?>"<?php echo $k === $cfg_ref ? ' selected' : ''; ?>><?php echo $h($l); ?></option><?php } ?>
                            <option value=""<?php echo $cfg_ref === '' ? ' selected' : ''; ?>>(nothing – only the variables of this theme)</option>
                        </select></label>
                    <?php if ($cmiss) { ?>
                        <p class="cc-missing-info"><b><?php echo (int) $cmiss; ?> variable(s) of the original are missing in this theme</b> (marked below; the sections that contain them are open).
                            Tick "add to the theme" for each one you want, or <button type="button" class="cc-outline" id="cc-add-all">tick all missing</button>. Unticked ones are not added.</p>
                    <?php } elseif ($cref !== null) { ?>
                        <p class="cc-hint">The theme has all variables of the original.</p>
                    <?php } ?>
                    <?php if ($norule || $differs) { ?>
                        <div class="cc-missing-info<?php echo $norule ? ' cc-var-missing' : ''; ?>">
                            <?php if ($norule) { ?>
                                <b>Some missing settings have no rules in this theme</b> (marked "no rule of the theme uses it" / missing switches) – adding the variables alone changes nothing.
                            <?php } else { ?>
                                <b>The rules (PART B) of this theme differ from the original</b> – e.g. the plugin was updated with fixed or new rules, or you changed rules yourself.
                            <?php } ?>
                            Take the rules from the original:
                            <?php foreach (array_keys($norule + $differs) as $crole) {
                                $rchk = $repost && !empty($_POST['rules']['cfg'][$crole]); ?>
                                <label class="cc-add"><input type="checkbox" name="<?php echo $h('rules[cfg][' . $crole . ']'); ?>" value="1"<?php echo $rchk ? ' checked' : ''; ?>>
                                    replace the rules (PART B) of <b><?php echo $h($crole); ?>.css</b> with the rules of the original<?php echo isset($norule[$crole]) ? ' (needed for the missing settings)' : ''; ?></label>
                            <?php } ?>
                            <span class="cc-hint">Your values (PART A) and your section "Extra rules" / "Your own rules" are kept; other changes of the rules are replaced (the old files stay in the version history).</span>
                        </div>
                    <?php } ?>
                </div>
                <div class="cc-tpl" data-tpl="cfg"><?php echo $render('cfg', $cmodel); ?></div>
                <p class="cc-hint">Saving stores all files of the theme as a new version (the saved files are changed – unsaved editor changes are not used). Only the values of PART A change;
                    added variables go into the matching section of PART A. Colours: pick one or type any CSS value.</p>
                <div class="cc-row cc-new-buttons">
                    <button type="submit" name="action" value="configure_save">Save theme</button>
                    <button type="button" class="cc-outline" id="btn-create-cancel">Cancel</button>
                </div>
            <?php } else { ?>
                <h3>Create a new theme</h3>
                <div class="cc-new-top">
                    <label class="cc-field"><span>Name of the new theme</span>
                        <input type="text" name="new_theme_name" maxlength="40" placeholder="e.g. custom-green" value="<?php echo $h($repost ? (string) ($_POST['new_theme_name'] ?? '') : ''); ?>"></label>
                    <fieldset class="cc-start">
                        <legend>Start from</legend>
                        <label><input type="radio" name="new_start" value="blank"<?php echo $start_sel === 'blank' ? ' checked' : ''; ?>> <b>Blank</b> – empty main.css, light-mode.css and dark-mode.css</label>
                        <label><input type="radio" name="new_start" value="template"<?php echo $start_sel !== 'blank' ? ' checked' : ''; ?><?php echo $tpl_list ? '' : ' disabled'; ?>> <b>Template</b>
                            <select name="new_template" id="cc-new-template">
                                <?php foreach ($tpl_list as $k => $l) { ?><option value="<?php echo $h($k); ?>"<?php echo $k === $tpl_sel ? ' selected' : ''; ?>><?php echo $h($l); ?></option><?php } ?>
                            </select> – with the settings below</label>
                    </fieldset>
                </div>
                <?php foreach ($tpl_list as $tk => $tl) {
                    $tfiles = customcss_template_files($tk);
                    if ($tfiles === null) {
                        continue;
                    } ?>
                    <div class="cc-tpl" data-tpl="<?php echo $h($tk); ?>"<?php echo ($start_sel !== 'blank' && $tk === $tpl_sel) ? '' : ' hidden'; ?>>
                        <?php echo $render($tk, customcss_config_model($tfiles, null)); ?>
                        <p class="cc-hint">Only the values of PART A change; the rules (PART B) and extra files are copied from the template. Colours: pick one or type any CSS value (e.g. var(--colour-brand-primary-default), rgba(0,0,0,.25)).</p>
                    </div>
                <?php } ?>
                <div class="cc-row cc-new-buttons">
                    <button type="submit" name="action" value="create_new">Create</button>
                    <button type="button" class="cc-outline" id="btn-create-cancel">Cancel</button>
                </div>
            <?php } ?>
        </form>
    </div>

    <!-- Duplicate a theme (the name is asked in a popup) - since 1.28 -->
    <form method="post" action="<?php echo $h($self); ?>" id="cc-dup-form" hidden>
        <?php generateFormToken('customcss_dup'); ?>
        <input type="hidden" name="action" value="duplicate">
        <input type="hidden" name="dup_theme" value="<?php echo $h($theme); ?>">
        <input type="hidden" name="dup_name" id="cc-dup-name" value="">
    </form>

    <form id="css-form" method="post" action="<?php echo $h($self); ?>"<?php echo $panel_mode !== '' ? ' hidden' : ''; ?>>
        <?php generateFormToken('customcss'); ?>
        <input type="hidden" name="theme" value="<?php echo $h($theme); ?>">
        <input type="hidden" name="files_json" id="files-json" value="">
        <input type="hidden" name="editor_dirty" id="editor-dirty" value="0">
        <input type="hidden" name="action" id="cc-action" value="" disabled>
        <input type="hidden" name="file_name" id="cc-file-name" value="">

        <div class="cc-layout">

            <!-- LEFT COLUMN: file tabs, editor, syntax check, buttons -->
            <div class="cc-main">
                <div class="cc-tabs" id="cc-tabs" role="tablist" aria-label="Theme files"></div>
                <?php if ($theme !== '' && in_array($theme, $themes ?? array(), true)) { ?>
                    <!-- shown behind "+ Add CSS file" (moved there by the script) - since 1.29 -->
                    <button type="button" class="cc-tab-cfg" id="btn-configure" title="Change the settings of this theme in a form (shapes, sizes, fonts, colours, login page)">Configurator</button>
                <?php } ?>
                <div class="cc-toolbar">
                    <span class="cc-info" id="cc-file-info"></span>
                    <span>
                        <button type="button" class="cc-outline" id="btn-orig" title="Replace the content of this file in the editor with the original shipped with the plugin (not saved until you click Save; Undo brings your version back)" hidden>Load original</button>
                        <button type="button" class="cc-outline cc-danger" id="btn-delfile" title="Delete this extra file from the theme" hidden>Delete file</button>
                        <button type="button" class="cc-outline" id="btn-undo" title="Undo (Ctrl+Z / Cmd+Z)"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg> Undo</button>
                        <button type="button" class="cc-outline" id="btn-redo" title="Redo (Ctrl+Y / Cmd+Shift+Z)"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg> Redo</button>
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
                                <button type="submit" name="action" value="deactivate" class="cc-outline">Deactivate</button>
                            <?php } else { ?>
                                <button type="submit" name="action" value="activate">Activate</button>
                            <?php } ?>
                            <button type="submit" name="action" value="delete" class="cc-outline">Delete theme</button>
                            <button type="button" class="cc-outline" id="btn-duplicate" title="Copy the saved files of this theme into a new theme">Duplicate</button>
                        </div>
                    <?php } else { ?>
                        <p><i>No themes yet. Click "Create new theme".</i></p>
                    <?php } ?>
                    <p>Active on the site: <b><?php echo $h($active_label); ?></b></p>

                    <h4>Add a new theme</h4>
                    <div class="cc-row">
                        <button type="button" id="btn-create-new" aria-controls="cc-new">Create new theme</button>
                    </div>

                    <h4>Download / upload (.zip)</h4>
                    <?php if (!customcss_zip_available()) { ?>
                        <p class="cc-hint">The PHP zip extension is missing (install php-zip and restart PHP-FPM).</p>
                    <?php } else { ?>
                        <div class="cc-row">
                            <?php if ($theme !== '') { ?><a class="cc-outline" download href="<?php echo $h($self . '?download=' . rawurlencode($theme)); ?>" title="All files of the theme as a .zip archive (saved version)">Download "<?php echo $h($theme); ?>"</a><?php } ?>
                            <?php if ($themes) { ?><a class="cc-outline" download href="<?php echo $h($self . '?download_all=1'); ?>" title="All themes, one folder per theme">Download all themes</a><?php } ?>
                        </div>
                        <label class="cc-field"><span>Upload a theme (.zip with CSS files)</span>
                            <input type="file" name="theme_zip" accept=".zip,application/zip" form="cc-import-form"></label>
                        <label class="cc-field"><span>Theme name (optional - default: folder or archive name)</span>
                            <input type="text" name="import_name" maxlength="40" form="cc-import-form" placeholder="e.g. custom-green"></label>
                        <div class="cc-row"><button type="submit" form="cc-import-form" name="action" value="import">Upload theme</button></div>
                        <p class="cc-hint">Missing main.css, light-mode.css or dark-mode.css: you can choose the matching files from the archive or let the plugin create empty ones.</p>
                    <?php } ?>
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
                    <p class="cc-hint">When enabled, every page shows a small round button that lets each user switch between light and dark mode - also when no theme is active.
                        For logged-in users the button sets their ResourceSpace appearance preference (Light / Dark / Device default, the same setting as on the user preferences page),
                        so the built-in dark mode of ResourceSpace switches too; the active theme switches between light-mode.css and dark-mode.css. Other visitors: the choice is kept in a browser cookie.</p>
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

                <details class="cc-box" data-box="reset"<?php echo $open_box === 'reset' ? ' open' : ''; ?>>
                    <summary><h3>Reset plugin</h3></summary>
                    <p>Deletes <b>everything the plugin stored</b>: all themes (also your own), the version history and the settings in
                        <code class="cc-path"><?php echo $h(customcss_base_dir()); ?></code>. The original themes are created again; no theme is active until you activate one.
                        <b>This cannot be undone.</b></p>
                    <label class="cc-field"><span>To confirm, type <b>RESET</b></span>
                        <input type="text" name="reset_confirm" id="reset-confirm" autocomplete="off" spellcheck="false"></label>
                    <div class="cc-row"><button type="submit" name="action" value="reset" id="btn-reset">Reset plugin</button></div>
                </details>
            </div>

        </div>
    </form>

    <!-- Upload of a theme archive (the fields are in the Themes box, linked with form="cc-import-form") -->
    <form id="cc-import-form" method="post" action="<?php echo $h($self); ?>" enctype="multipart/form-data">
        <?php generateFormToken('customcss_import'); ?>
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
        var origFiles = <?php echo json_encode((object) $orig_files, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        var pluginsUrl = <?php echo json_encode($plugins_url); ?>;
        var selfUrl = <?php echo json_encode($self); ?>;
        var baseUrlShort = <?php echo json_encode($baseurl_short); ?>;
        var currentTheme = <?php echo json_encode($theme); ?>;
        var themePath = <?php echo json_encode($theme !== '' ? 'filestore/customcss/themes/' . $theme . '/' : ''); ?>;
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
                    x.title = 'Delete this file from the theme';
                    x.addEventListener('click', function (e) { e.stopPropagation(); deleteFile(f); });
                    b.appendChild(x);
                }
                b.addEventListener('click', function () { selectFile(f); });
                tabsEl.appendChild(b);
            });
            var add = document.createElement('button');
            add.type = 'button';
            add.className = 'cc-tab-add cc-outline';
            add.textContent = '+ Add CSS file';
            add.title = 'Add another CSS file to the theme (loaded after main.css in both modes)';
            add.addEventListener('click', addFile);
            tabsEl.appendChild(add);
            // "Configurator" right behind "+ Add CSS file" (since 1.29)
            var cfg = document.getElementById('btn-configure');
            if (cfg) { tabsEl.appendChild(cfg); }
        }

        function selectFile(f) {
            current = f;
            editor.swapDoc(f.doc);
            infoEl.textContent = (themePath || '') + f.name + '.css – ' + role(f.name);
            lintFileEl.textContent = f.name + '.css';
            if (f.name === 'light-mode' || f.name === 'dark-mode') { previewMode.value = f.name; }
            document.getElementById('btn-orig').hidden = !Object.prototype.hasOwnProperty.call(origFiles, f.name);
            document.getElementById('btn-delfile').hidden = REQUIRED.indexOf(f.name) !== -1;
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
            files = files.filter(function (x) { return x !== f; });
            if (current === f) { selectFile(findFile('main')); } else { renderTabs(); }
        }

        // Deletes an extra file: a file that was never saved is only removed from the editor;
        // a saved file is deleted on the server right away and the theme is stored as a new version
        // (other unsaved changes stay in the editor). Since 1.22.
        function deleteFile(f) {
            if (!f || REQUIRED.indexOf(f.name) !== -1) { return; }
            if (!Object.prototype.hasOwnProperty.call(savedFiles, f.name) || !currentTheme) {
                if (confirm('Remove the new file ' + f.name + '.css? It has not been saved yet.')) { removeFile(f); }
                return;
            }
            if (!confirm('Delete ' + f.name + '.css from the theme "' + currentTheme + '" now? The theme is saved as a new version without this file '
                + '(older versions still contain it). Other unsaved changes stay in the editor.')) { return; }
            removeFile(f);
            var act = document.getElementById('cc-action');
            act.disabled = false;
            act.value = 'delete_file';
            document.getElementById('cc-file-name').value = f.name;
            document.getElementById('files-json').value = JSON.stringify(collectFiles());
            document.getElementById('editor-dirty').value = isDirty() ? '1' : '0';
            leaving = true;
            form.submit(); // no submit event: the checks of the other buttons do not apply
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
        document.getElementById('btn-delfile').addEventListener('click', function () { deleteFile(current); });
        document.getElementById('btn-orig').addEventListener('click', function () {
            if (!current || !Object.prototype.hasOwnProperty.call(origFiles, current.name)) { return; }
            if (current.doc.getValue() === origFiles[current.name]) { alert(current.name + '.css is already the same as the original.'); return; }
            if (!confirm('Replace ' + current.name + '.css in the editor with the original of the theme? Not saved until you click "Save"; Undo brings your version back.')) { return; }
            current.doc.setValue(origFiles[current.name]);
            editor.focus();
        });

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
        // Mode chosen with the light / dark switch of this plugin (hooks/all.php), if it is on
        var switchMode = typeof window.customcssEffectiveMode === 'function' ? window.customcssEffectiveMode() : '';
        function applyEditorTheme(choice) {
            var name = choice;
            if (choice === 'auto') {
                var dark = switchMode ? switchMode === 'dark' : pageIsDark();
                name = dark ? 'cc-dark-blue' : 'default';
            }
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
        // "Auto" follows the light / dark switch at once (since 1.22) and the device setting
        document.addEventListener('customcss:mode', function (e) {
            switchMode = (e && e.detail && e.detail.mode) || '';
            if (editorThemeSelect && editorThemeSelect.value === 'auto') { applyEditorTheme('auto'); }
        });
        if (window.matchMedia) {
            var cmq = window.matchMedia('(prefers-color-scheme: dark)');
            var onDevice = function () {
                if (typeof window.customcssEffectiveMode === 'function') { switchMode = window.customcssEffectiveMode(); }
                if (editorThemeSelect && editorThemeSelect.value === 'auto') { setTimeout(function () { applyEditorTheme('auto'); }, 100); }
            };
            if (cmq.addEventListener) { cmq.addEventListener('change', onDevice); }
        }
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
                [].forEach.call(doc.querySelectorAll('link[rel="stylesheet"][href*="/customcss/themes/"]'), function (l) {
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

        // Enter in the reset field must not submit the form with the first button (Save)
        document.getElementById('reset-confirm').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); }
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
            if (act === 'save') {
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
            if (act === 'reset') {
                if (document.getElementById('reset-confirm').value.trim() !== 'RESET') {
                    alert('Type RESET to confirm.');
                    e.preventDefault();
                    return;
                }
                if (!confirm('Delete ALL themes, versions and settings of Custom CSS? This cannot be undone.')) {
                    e.preventDefault();
                    return;
                }
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

        // Panel "Create a new theme" / "Configurator" (since 1.25 / 1.28): replaces the editor while open
        (function () {
            var panel = document.getElementById('cc-new');
            if (!panel) { return; }
            var editorForm = document.getElementById('css-form');
            var tplSel = document.getElementById('cc-new-template');
            function current() {
                if (panel.getAttribute('data-mode') === 'configure') { return 'cfg'; }
                var r = panel.querySelector('input[name=new_start]:checked');
                return r && r.value === 'template' && tplSel ? tplSel.value : '';
            }
            function showTemplate() {
                var cur = current();
                panel.querySelectorAll('.cc-tpl').forEach(function (d) {
                    var on = d.getAttribute('data-tpl') === cur;
                    d.hidden = !on;
                    // only the fields of the chosen template are sent
                    d.querySelectorAll('input, select').forEach(function (el) { el.disabled = !on; });
                });
            }
            function openPanel() {
                panel.hidden = false;
                if (editorForm) { editorForm.hidden = true; }
                showTemplate();
                window.scrollTo({top: panel.getBoundingClientRect().top + window.pageYOffset - 20, behavior: 'smooth'});
            }
            function closePanel() {
                if (panel.getAttribute('data-mode') === 'configure') {
                    // the configurator was opened with a link: back to the editor of the theme
                    leaving = true;
                    window.location.href = selfUrl + '?theme=' + encodeURIComponent(currentTheme);
                    return;
                }
                panel.hidden = true;
                if (editorForm) { editorForm.hidden = false; }
            }
            panel.querySelectorAll('input[name=new_start]').forEach(function (r) { r.addEventListener('change', showTemplate); });
            if (tplSel) {
                tplSel.addEventListener('change', function () {
                    var t = panel.querySelector('input[name=new_start][value=template]');
                    if (t) { t.checked = true; }
                    showTemplate();
                });
            }
            // colour picker <-> text field
            panel.querySelectorAll('.cc-var-input').forEach(function (box) {
                var pick = box.querySelector('input.cc-color'), txt = box.querySelector('input.cc-color-text');
                if (!pick || !txt) { return; }
                pick.addEventListener('input', function () { txt.value = pick.value.toUpperCase(); });
                txt.addEventListener('input', function () {
                    var v = txt.value.trim();
                    if (/^#[0-9a-f]{3}$/i.test(v)) { v = '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]; }
                    if (/^#[0-9a-f]{6}$/i.test(v)) { pick.value = v.toLowerCase(); }
                });
            });
            // configurator: a changed value of a missing variable ticks "add to the theme"
            panel.querySelectorAll('.cc-var-missing').forEach(function (row) {
                var box = row.querySelector('.cc-add-box');
                if (!box) { return; }
                row.querySelectorAll('.cc-var-input input, .cc-var-input select').forEach(function (el) {
                    el.addEventListener('change', function () { box.checked = true; });
                });
            });
            // changing an own value (e.g. --cc-login-sso-label) switches its "...-own" setting to 1 (since 1.30)
            panel.querySelectorAll('.cc-var-input input[name], .cc-var-input select[name]').forEach(function (el) {
                var m = /^(var\[[^\]]*\]\[[^\]]*\]\[)(--[A-Za-z0-9_-]+)\]$/.exec(el.name);
                if (!m || /-own$/.test(m[2])) { return; }
                var own = panel.querySelector('select[name="' + m[1] + m[2] + '-own]"]');
                if (!own) { return; }
                el.addEventListener('change', function () {
                    if (own.value !== '1') {
                        own.value = '1';
                        own.dispatchEvent(new Event('change')); // also ticks "add to the theme" when the setting is missing
                    }
                });
            });
            var addAll = document.getElementById('cc-add-all');
            if (addAll) {
                addAll.addEventListener('click', function () {
                    panel.querySelectorAll('.cc-add-box').forEach(function (b) { b.checked = true; });
                });
            }
            var refSel = document.getElementById('cc-conf-ref');
            if (refSel) {
                refSel.addEventListener('change', function () {
                    leaving = true;
                    window.location.href = selfUrl + '?configure=' + encodeURIComponent(refSel.getAttribute('data-theme')) + '&ref=' + encodeURIComponent(refSel.value);
                });
            }
            document.getElementById('btn-create-new').addEventListener('click', function () {
                if (panel.getAttribute('data-mode') === 'configure') {
                    if (isDirty() && !confirm('You have unsaved changes. Leave the editor?')) { return; }
                    leaving = true;
                    window.location.href = selfUrl + '?theme=' + encodeURIComponent(currentTheme) + '#create';
                    return;
                }
                openPanel();
                var n = panel.querySelector('input[name=new_theme_name]');
                if (n) { n.focus(); }
            });
            document.getElementById('btn-create-cancel').addEventListener('click', closePanel);
            var cfgBtn = document.getElementById('btn-configure');
            if (cfgBtn) {
                cfgBtn.addEventListener('click', function () {
                    if (isDirty() && !confirm('You have unsaved changes in the editor. The configurator works on the saved files - leave without saving?')) { return; }
                    leaving = true;
                    window.location.href = selfUrl + '?configure=' + encodeURIComponent(currentTheme);
                });
            }
            var dupBtn = document.getElementById('btn-duplicate');
            if (dupBtn) {
                dupBtn.addEventListener('click', function () {
                    var name = window.prompt('Name of the copy of "' + currentTheme + '" (letters, numbers, "-" and "_"; other characters become "-"):', currentTheme + ' (copy)');
                    if (name === null || !name.trim()) { return; }
                    if (isDirty() && !confirm('The copy is made from the saved files. Unsaved changes in the editor are lost when the page reloads. Continue?')) { return; }
                    document.getElementById('cc-dup-name').value = name.trim();
                    leaving = true;
                    document.getElementById('cc-dup-form').submit();
                });
            }
            if (window.location.hash === '#create' && panel.getAttribute('data-mode') !== 'configure') {
                openPanel();
            } else if (!panel.hidden) {
                showTemplate();
            }
        })();

        // Upload / import of a theme archive / new theme: warn about unsaved editor changes (since 1.22)
        ['cc-import-form', 'cc-import-map', 'cc-new-form'].forEach(function (id) {
            var f = document.getElementById(id);
            if (!f) { return; }
            f.addEventListener('submit', function (e) {
                if (isDirty() && !confirm('You have unsaved changes in the editor. They are lost when the page reloads. Continue?')) {
                    e.preventDefault();
                    return;
                }
                leaving = true;
            });
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
