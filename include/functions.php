<?php
// Helper functions shared by the hook and the setup page.
//
// All data of the plugin is stored in <filestore>/customcss/ (since 1.16; before that in
// <ResourceSpace root>/Custom/, moved by customcss_relocate()). <filestore> = $storagedir.
//
// A theme is a folder with at least three CSS files (since 1.13) - public, loaded by browsers:
//   <filestore>/customcss/themes/<theme>/main.css         shared by light and dark mode, loaded first
//   <filestore>/customcss/themes/<theme>/<extra>.css      optional extra files, loaded after main.css
//   <filestore>/customcss/themes/<theme>/light-mode.css   loaded in light mode
//   <filestore>/customcss/themes/<theme>/dark-mode.css    loaded in dark mode
// Not public (folder name contains a key derived from $scramble_key):
//   <filestore>/customcss/data_<key>/settings.json                         active theme, switch
//   <filestore>/customcss/data_<key>/versions/<theme>/v<N>_<date>_<time>/  version history
//   <filestore>/customcss/data_<key>/legacy-<date>/                        files of older layouts
// Original themes shipped with the plugin ("Restore original"):
//   plugins/customcss/defaults/<theme>/*.css
//
// ResourceSpace shows its error page for every PHP warning, also for calls silenced with "@",
// so files and folders are always checked before they are used.

const CUSTOMCSS_REQUIRED      = array('main', 'light-mode', 'dark-mode');
const CUSTOMCSS_KEEP_VERSIONS = 30;
const CUSTOMCSS_DEFAULT_THEME = 'custom-blue';
// main.css written by the conversion of 1.13 when the old themes had no shared part
const CUSTOMCSS_MAIN_PLACEHOLDER = "/* main.css - shared by light and dark mode */\n";

// $storagedir of ResourceSpace (default: <ResourceSpace root>/filestore)
function customcss_storage_dir()
{
    $s = (string) ($GLOBALS['storagedir'] ?? '');
    return rtrim($s !== '' ? $s : dirname(__FILE__, 4) . '/filestore', '/');
}

// <filestore>/customcss - everything the plugin stores (removed by "Reset plugin")
function customcss_base_dir()
{
    return customcss_storage_dir() . '/customcss';
}

// Not public data: settings, version history, old files
function customcss_dir()
{
    $key = (string) ($GLOBALS['scramble_key'] ?? '');
    return customcss_base_dir() . '/data' . ($key !== '' ? '_' . substr(md5($key . 'customcss'), 0, 12) : '');
}

function customcss_themes_dir()
{
    return customcss_base_dir() . '/themes';
}

// URL of the themes folder ($storageurl of ResourceSpace, default <baseurl>/filestore)
function customcss_themes_url()
{
    $u = (string) ($GLOBALS['storageurl'] ?? '');
    if ($u === '') {
        $u = rtrim((string) ($GLOBALS['baseurl_short'] ?? '/'), '/') . '/filestore';
    }
    return rtrim($u, '/') . '/customcss/themes/';
}

// Folder of the plugin's data before 1.16
function customcss_old_dir()
{
    return dirname(__FILE__, 4) . '/Custom';
}

function customcss_theme_dir($theme)
{
    return customcss_themes_dir() . '/' . $theme;
}

function customcss_versions_dir($theme)
{
    return customcss_dir() . '/versions/' . $theme;
}

function customcss_defaults_dir()
{
    return dirname(__FILE__, 2) . '/defaults';
}

// Converts any text to a safe theme or file name: a-z, 0-9, "-" and "_" (max 40 chars)
function customcss_slug($name)
{
    $name = strtolower(trim((string) $name));
    $name = preg_replace('/[^a-z0-9_-]+/', '-', $name);
    return substr(trim($name, '-_'), 0, 40);
}

function customcss_name_valid($name)
{
    return is_string($name) && $name !== '' && $name === customcss_slug($name);
}

// Short description of a theme file (shown above the editor)
function customcss_file_role($name)
{
    $roles = array(
        'main'       => 'shared by light and dark mode, loaded first',
        'light-mode' => 'colours of the light mode, loaded last in light mode',
        'dark-mode'  => 'colours of the dark mode, loaded last in dark mode',
    );
    return $roles[$name] ?? 'extra file, loaded after main.css in both modes';
}

// Deletes a folder with all its files (no "@")
function customcss_rrmdir($dir)
{
    if (is_link($dir) || is_file($dir)) {
        return unlink($dir);
    }
    if (!is_dir($dir)) {
        return true;
    }
    foreach (scandir($dir) ?: array() as $e) {
        if ($e !== '.' && $e !== '..') {
            customcss_rrmdir($dir . '/' . $e);
        }
    }
    return rmdir($dir);
}

// ---------------------------------------------------------------------------
// Theme files
// ---------------------------------------------------------------------------

// File names in a theme folder in loading order: main, extras (A-Z), light-mode, dark-mode.
// The three required names are always included (also when the file is missing).
function customcss_file_names($dir)
{
    $extras = array();
    foreach (glob($dir . '/*.css') ?: array() as $f) {
        $n = basename($f, '.css');
        if (customcss_name_valid($n) && !in_array($n, CUSTOMCSS_REQUIRED, true)) {
            $extras[] = $n;
        }
    }
    sort($extras);
    return array_merge(array('main'), $extras, array('light-mode', 'dark-mode'));
}

// Contents of all files of a theme folder: name => CSS (missing required files = '')
function customcss_read_files($dir)
{
    $out = array();
    foreach (customcss_file_names($dir) as $n) {
        $f = $dir . '/' . $n . '.css';
        $out[$n] = (is_file($f) && is_readable($f)) ? (string) file_get_contents($f) : '';
    }
    return $out;
}

// Checks files sent by the editor: name => CSS. Returns the cleaned list or null (+ $error).
function customcss_clean_files($files, &$error)
{
    $error = '';
    if (!is_array($files)) {
        $error = 'The editor content was not received.';
        return null;
    }
    $out = array();
    foreach ($files as $name => $css) {
        $name = (string) $name;
        if (!customcss_name_valid($name) || !is_string($css)) {
            $error = 'Invalid file name: ' . $name;
            return null;
        }
        $out[$name] = str_replace("\r\n", "\n", $css);
    }
    foreach (CUSTOMCSS_REQUIRED as $r) {
        if (!isset($out[$r])) {
            $error = 'The file ' . $r . '.css is missing - every theme needs main.css, light-mode.css and dark-mode.css.';
            return null;
        }
    }
    if (count($out) > 30) {
        $error = 'A theme can have at most 30 files.';
        return null;
    }
    return $out;
}

// Writes the files to a folder and removes CSS files that are not in the list
function customcss_write_files($dir, array $files)
{
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }
    if (!is_writable($dir)) {
        return false;
    }
    foreach ($files as $name => $css) {
        if (file_put_contents($dir . '/' . $name . '.css', $css, LOCK_EX) === false) {
            return false;
        }
    }
    foreach (glob($dir . '/*.css') ?: array() as $f) {
        if (!array_key_exists(basename($f, '.css'), $files)) {
            unlink($f);
        }
    }
    return true;
}

// ---------------------------------------------------------------------------
// Themes
// ---------------------------------------------------------------------------

function customcss_theme_exists($theme)
{
    return customcss_name_valid($theme) && is_file(customcss_theme_dir($theme) . '/main.css');
}

// Names of all themes (folders with main.css)
function customcss_list()
{
    $out = array();
    foreach (glob(customcss_themes_dir() . '/*', GLOB_ONLYDIR) ?: array() as $d) {
        $n = basename($d);
        if (customcss_theme_exists($n)) {
            $out[] = $n;
        }
    }
    sort($out);
    return $out;
}

// Names of the original themes shipped with the plugin
function customcss_defaults()
{
    $out = array();
    foreach (glob(customcss_defaults_dir() . '/*', GLOB_ONLYDIR) ?: array() as $d) {
        $n = basename($d);
        if (customcss_name_valid($n) && is_file($d . '/main.css')) {
            $out[] = $n;
        }
    }
    sort($out);
    return $out;
}

function customcss_read_theme($theme)
{
    return customcss_read_files(customcss_theme_dir($theme));
}

// Saves all files of a theme and stores them as the next version.
// Returns the name of the new version or false.
function customcss_save_theme($theme, array $files)
{
    if (!customcss_name_valid($theme) || !customcss_write_files(customcss_theme_dir($theme), $files)) {
        return false;
    }
    return customcss_snapshot($theme);
}

// Deletes the theme folder (the version history is kept)
function customcss_delete_theme($theme)
{
    if (!customcss_theme_exists($theme) || !customcss_rrmdir(customcss_theme_dir($theme))) {
        return false;
    }
    $s = customcss_settings();
    if ($s['active'] === $theme) {
        $s['active'] = '';
        customcss_save_settings($s);
    }
    return true;
}

// Replaces a theme with its original (or recreates it). Returns the new version name or false.
function customcss_restore_default($theme)
{
    $dir = customcss_defaults_dir() . '/' . $theme;
    if (!in_array($theme, customcss_defaults(), true)) {
        return false;
    }
    return customcss_save_theme($theme, customcss_read_files($dir));
}

// ---------------------------------------------------------------------------
// Settings: active theme and light/dark mode switch
// ---------------------------------------------------------------------------

function customcss_settings_file()
{
    return customcss_dir() . '/settings.json';
}

function customcss_settings()
{
    $s = array('format' => 2, 'active' => '', 'switch' => false, 'default_mode' => 'auto');
    $f = customcss_settings_file();
    if (is_file($f) && is_readable($f)) {
        $json = json_decode((string) file_get_contents($f), true);
        if (is_array($json)) {
            $s = array_merge($s, array_intersect_key($json, $s));
        }
    }
    $s['format'] = 2;
    $s['active'] = customcss_slug((string) $s['active']);
    $s['switch'] = !empty($s['switch']);
    if (!in_array($s['default_mode'], array('auto', 'light', 'dark'), true)) {
        $s['default_mode'] = 'auto';
    }
    return $s;
}

function customcss_save_settings(array $s)
{
    $dir = customcss_dir();
    if (!is_dir($dir) && (customcss_prepare_dirs() !== '' || !is_dir($dir))) {
        return false;
    }
    if (!is_writable($dir)) {
        return false;
    }
    return file_put_contents(customcss_settings_file(), json_encode($s, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// Active theme, '' when none
function customcss_active()
{
    $t = customcss_settings()['active'];
    return customcss_theme_exists($t) ? $t : '';
}

function customcss_set_active($theme)
{
    $s           = customcss_settings();
    $s['active'] = $theme;
    return customcss_save_settings($s);
}

// ---------------------------------------------------------------------------
// Version history: v<N>_<YYYYMMDD>_<HHMMSS>/ folders with all files of the theme
// ---------------------------------------------------------------------------

function customcss_version_parse($name)
{
    if (!is_string($name) || !preg_match('/^v(\d+)_(\d{8}_\d{6})$/', $name, $m)) {
        return null;
    }
    return array('num' => (int) $m[1], 'stamp' => $m[2]);
}

// Version names of a theme, oldest first
function customcss_versions($theme)
{
    $out = array();
    foreach (glob(customcss_versions_dir($theme) . '/v*', GLOB_ONLYDIR) ?: array() as $d) {
        $n = basename($d);
        if (customcss_version_parse($n) !== null) {
            $out[] = $n;
        }
    }
    usort($out, function ($a, $b) {
        $pa = customcss_version_parse($a);
        $pb = customcss_version_parse($b);
        return array($pa['num'], $pa['stamp']) <=> array($pb['num'], $pb['stamp']);
    });
    return $out;
}

// Number of the newest version (0 when there is none)
function customcss_current_version($theme)
{
    $v = customcss_versions($theme);
    return $v ? customcss_version_parse(end($v))['num'] : 0;
}

// e.g. "v3 - 2026-11-11 21:30:12 (4 files)"
function customcss_version_label($theme, $name)
{
    $p     = customcss_version_parse($name);
    $dt    = $p ? DateTime::createFromFormat('Ymd_His', $p['stamp']) : false;
    $count = count(glob(customcss_versions_dir($theme) . '/' . $name . '/*.css') ?: array());
    return ($p ? 'v' . $p['num'] . ' - ' : '') . ($dt ? $dt->format('Y-m-d H:i:s') : $name)
        . ' (' . $count . ' file' . ($count === 1 ? '' : 's') . ')';
}

function customcss_version_files($theme, $name)
{
    if (customcss_version_parse($name) === null) {
        return null;
    }
    $dir = customcss_versions_dir($theme) . '/' . $name;
    return is_dir($dir) ? customcss_read_files($dir) : null;
}

// Stores the current files of a theme as the next version and keeps the newest
// CUSTOMCSS_KEEP_VERSIONS versions. Returns the version name or false.
function customcss_snapshot($theme)
{
    $src = customcss_theme_dir($theme);
    if (!customcss_theme_exists($theme)) {
        return false;
    }
    $base = customcss_versions_dir($theme);
    if (!is_dir($base) && !mkdir($base, 0755, true)) {
        return false;
    }
    $name = 'v' . (customcss_current_version($theme) + 1) . '_' . date('Ymd_His');
    $dir  = $base . '/' . $name;
    if (is_dir($dir) || !mkdir($dir, 0755)) {
        return false;
    }
    foreach (glob($src . '/*.css') ?: array() as $f) {
        if (!copy($f, $dir . '/' . basename($f))) {
            return false;
        }
    }
    $versions = customcss_versions($theme);
    foreach (array_slice($versions, 0, max(0, count($versions) - CUSTOMCSS_KEEP_VERSIONS)) as $old) {
        customcss_rrmdir($base . '/' . $old);
    }
    return $name;
}

function customcss_delete_version($theme, $name)
{
    if (customcss_version_parse($name) === null || !in_array($name, customcss_versions($theme), true)) {
        return false;
    }
    return customcss_rrmdir(customcss_versions_dir($theme) . '/' . $name);
}

// Deletes all versions except the newest. Returns the number of deleted versions.
function customcss_clear_history($theme)
{
    $versions = customcss_versions($theme);
    array_pop($versions);
    $count = 0;
    foreach ($versions as $v) {
        if (customcss_rrmdir(customcss_versions_dir($theme) . '/' . $v)) {
            $count++;
        }
    }
    return $count;
}

// ---------------------------------------------------------------------------
// First start: folders, original themes, migration from the old layout
// ---------------------------------------------------------------------------

// Creates <filestore>/customcss/themes and the data folder when they are missing.
// Returns '' or an error message.
function customcss_prepare_dirs()
{
    $storage = customcss_storage_dir();
    foreach (array(customcss_themes_dir(), customcss_dir()) as $dir) {
        if (is_dir($dir)) {
            if (!is_writable($dir)) {
                return 'The web server cannot write to ' . $dir . ' - themes cannot be saved.';
            }
            continue;
        }
        if (!is_dir($storage) || (!is_writable($storage) && !is_dir(customcss_base_dir()))) {
            return 'The filestore folder ' . $storage . ' does not exist or the web server cannot write to it.';
        }
        if (is_dir(customcss_base_dir()) && !is_writable(customcss_base_dir())) {
            return 'The web server cannot write to ' . customcss_base_dir() . '.';
        }
        if (!mkdir($dir, 0755, true)) {
            return 'Could not create ' . $dir . '.';
        }
    }
    return '';
}

// Copies a folder with all its content (no "@")
function customcss_copy_tree($src, $dst)
{
    if (is_file($src)) {
        return is_dir(dirname($dst)) || mkdir(dirname($dst), 0755, true) ? copy($src, $dst) : false;
    }
    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        return false;
    }
    $ok = true;
    foreach (scandir($src) ?: array() as $e) {
        if ($e !== '.' && $e !== '..') {
            $ok = customcss_copy_tree($src . '/' . $e, $dst . '/' . $e) && $ok;
        }
    }
    return $ok;
}

// Moves the data of versions before 1.16 from <ResourceSpace root>/Custom to <filestore>/customcss:
//   Custom/themes -> themes, Custom/versions -> data/versions, other files (settings.json,
//   active_theme.txt, legacy-*) -> data. The files are copied; the old folder is then renamed to
//   Custom-moved-<date> (kept as a backup; when it cannot be renamed it stays as it is).
// Runs once: nothing happens when the new folders already hold data. Returns messages.
function customcss_relocate()
{
    $old    = customcss_old_dir();
    $marker = customcss_base_dir() . '/.relocated';
    if (!is_dir($old) || is_file($marker) || is_file(customcss_settings_file()) || glob(customcss_themes_dir() . '/*')) {
        return array();
    }
    if (customcss_prepare_dirs() !== '') {
        return array('The old theme folder ' . $old . ' could not be moved: ' . customcss_prepare_dirs());
    }
    $ok = true;
    foreach (scandir($old) ?: array() as $e) {
        if ($e === '.' || $e === '..') {
            continue;
        }
        $target = $e === 'themes' ? customcss_themes_dir() : customcss_dir() . '/' . $e;
        $ok     = customcss_copy_tree($old . '/' . $e, $target) && $ok;
    }
    if (!$ok) {
        return array('Some files of ' . $old . ' could not be copied to ' . customcss_base_dir() . ' - the old folder was kept, check the permissions.');
    }
    file_put_contents($marker, date('c'));
    $backup = $old . '-moved-' . date('Ymd_His');
    $moved  = is_writable(dirname($old)) && rename($old, $backup);
    return array('The themes and settings were moved from ' . $old . ' to ' . customcss_base_dir() . '.'
        . ($moved ? ' The old folder was kept as ' . $backup . ' (it can be deleted).' : ' The old folder ' . $old . ' was kept (it can be deleted).'));
}

// "Reset plugin": deletes all themes, the version history and the settings in <filestore>/customcss.
// The original themes are created again the next time the options page is opened.
function customcss_reset()
{
    if (!customcss_rrmdir(customcss_base_dir())) {
        return false;
    }
    // the marker stops an old <ResourceSpace root>/Custom folder from being copied back
    if (is_dir(customcss_old_dir()) && mkdir(customcss_base_dir(), 0755, true)) {
        file_put_contents(customcss_base_dir() . '/.relocated', date('c'));
    }
    return true;
}

// Creates the original themes that do not exist yet (e.g. after installing from a .zip archive).
// A theme that has a version history was deleted on purpose and is not recreated.
// Returns the names of the created themes.
function customcss_install_defaults()
{
    $created = array();
    if (!is_dir(customcss_themes_dir()) || !is_writable(customcss_themes_dir())) {
        return $created;
    }
    foreach (customcss_defaults() as $theme) {
        if (customcss_theme_exists($theme) || is_dir(customcss_versions_dir($theme))) {
            continue;
        }
        if (customcss_save_theme($theme, customcss_read_files(customcss_defaults_dir() . '/' . $theme)) !== false) {
            $created[] = $theme;
        }
    }
    return $created;
}

// Original themes whose main.css contains only the placeholder of the 1.13 conversion get the
// original main.css (login page, banner, shapes ...), stored as a new version. Files with any
// other content are never touched. Returns the names of the repaired themes. (since 1.15)
function customcss_repair_empty_main()
{
    $repaired = array();
    foreach (customcss_defaults() as $theme) {
        $file = customcss_theme_dir($theme) . '/main.css';
        $orig = customcss_defaults_dir() . '/' . $theme . '/main.css';
        if (!is_file($file) || !is_readable($file) || !is_writable($file) || !is_readable($orig)) {
            continue;
        }
        if (trim((string) file_get_contents($file)) !== trim(CUSTOMCSS_MAIN_PLACEHOLDER)) {
            continue;
        }
        if (file_put_contents($file, (string) file_get_contents($orig), LOCK_EX) !== false && customcss_snapshot($theme) !== false) {
            $repaired[] = $theme;
        }
    }
    return $repaired;
}

// Moves a file or folder into the legacy folder (no "@")
function customcss_legacy_move($path, $legacy, $sub)
{
    $target = $legacy . '/' . $sub;
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) {
        return false;
    }
    return rename($path, $target);
}

// Converts the old layout (before 1.13: one CSS file per theme, main/light/dark theme in the
// switch settings) to theme folders. Runs once; returns messages for the options page.
//   - the light/dark switch trio (main-blue + light-blue + dark-blue by default) becomes the theme
//     "custom-blue" (or "migrated-switch" when other themes were selected), keeping your edits,
//   - every other old theme X.css becomes the theme X (its CSS in main.css),
//   - the active theme and the switch settings are kept,
//   - the old files and their version history are moved to <data>/legacy-<date>/ (nothing is deleted).
function customcss_migrate()
{
    $themes = customcss_themes_dir();
    $file   = customcss_settings_file();
    $old    = array();
    if (is_file($file) && is_readable($file)) {
        $old = json_decode((string) file_get_contents($file), true);
        $old = is_array($old) ? $old : array();
    }
    if (($old['format'] ?? 0) === 2) {
        return array();
    }
    $flat = array();
    foreach (glob($themes . '/*.css') ?: array() as $f) {
        $n = basename($f, '.css');
        if (customcss_name_valid($n) && is_readable($f)) {
            $flat[$n] = (string) file_get_contents($f);
        }
    }
    $active_file = customcss_dir() . '/active_theme.txt';
    $old_active  = is_file($active_file) && is_readable($active_file) ? customcss_slug((string) file_get_contents($active_file)) : '';
    if (!$flat && !$old && $old_active === '') {
        return array(); // fresh installation, nothing to convert
    }
    if (customcss_prepare_dirs() !== '' || !is_writable(customcss_dir()) || (is_dir($themes) && !is_writable($themes))) {
        return array('The old theme files could not be converted: the web server cannot write to ' . customcss_dir() . '.');
    }

    $legacy   = customcss_dir() . '/legacy-' . date('Ymd_His');
    $messages = array();
    $created  = array();

    // 1. The light/dark switch trio -> one theme
    $main  = array_key_exists('main_theme', $old) ? customcss_slug((string) $old['main_theme']) : '';
    $light = customcss_slug((string) ($old['light_theme'] ?? 'light-blue'));
    $dark  = customcss_slug((string) ($old['dark_theme'] ?? 'dark-blue'));
    $trio  = '';
    if (isset($flat[$light]) || isset($flat[$dark])) {
        $is_default = $light === 'light-blue' && $dark === 'dark-blue' && in_array($main, array('', 'main-blue'), true);
        $trio       = $is_default ? CUSTOMCSS_DEFAULT_THEME : 'migrated-switch';
        $orig       = customcss_read_files(customcss_defaults_dir() . '/' . CUSTOMCSS_DEFAULT_THEME);
        $files      = array(
            'main'       => $main !== '' ? ($flat[$main] ?? ($is_default ? $orig['main'] : '')) : ($is_default ? $orig['main'] : CUSTOMCSS_MAIN_PLACEHOLDER),
            'light-mode' => $flat[$light] ?? ($is_default ? $orig['light-mode'] : ''),
            'dark-mode'  => $flat[$dark] ?? ($is_default ? $orig['dark-mode'] : ''),
        );
        if (!customcss_theme_exists($trio) && customcss_save_theme($trio, $files) !== false) {
            $created[] = $trio;
            $messages[] = 'Converted ' . trim(($main !== '' ? $main . '.css + ' : '') . $light . '.css + ' . $dark . '.css')
                . ' into the theme "' . $trio . '" (main.css, light-mode.css, dark-mode.css).';
        }
    }

    // 2. Every other old theme -> a theme folder with its CSS in main.css
    $used = array_filter(array($main, $light, $dark));
    foreach ($flat as $n => $css) {
        if ($trio !== '' && in_array($n, $used, true)) {
            continue;
        }
        if (customcss_theme_exists($n)) {
            continue;
        }
        $files = array(
            'main'       => $css,
            'light-mode' => "/* light-mode.css - colours of the light mode (empty: converted from the old theme $n.css) */\n",
            'dark-mode'  => "/* dark-mode.css - colours of the dark mode (empty: converted from the old theme $n.css) */\n",
        );
        if (customcss_save_theme($n, $files) !== false) {
            $created[]  = $n;
            $messages[] = 'Converted the old theme ' . $n . '.css into the theme "' . $n . '" (its CSS is in main.css).';
        }
    }

    // 3. Settings: active theme and switch
    $active = '';
    if (!empty($old['switch']) && $trio !== '') {
        $active = $trio;
    } elseif ($old_active !== '') {
        $active = ($trio !== '' && in_array($old_active, $used, true)) ? $trio : (customcss_theme_exists($old_active) ? $old_active : '');
    }
    $new = array(
        'format'       => 2,
        'active'       => $active,
        'switch'       => !empty($old['switch']),
        'default_mode' => in_array($old['default_mode'] ?? '', array('auto', 'light', 'dark'), true) ? $old['default_mode'] : 'auto',
    );

    // 4. Old files and their version history -> legacy folder
    foreach (array_keys($flat) as $n) {
        customcss_legacy_move($themes . '/' . $n . '.css', $legacy, 'themes/' . $n . '.css');
        // old history: v<N>_<date>_<time>.css files and current_version.txt directly in versions/<name>/
        $vdir = customcss_dir() . '/versions/' . $n;
        foreach (array_merge(glob($vdir . '/*.css') ?: array(), glob($vdir . '/current_version.txt') ?: array()) as $f) {
            customcss_legacy_move($f, $legacy, 'versions/' . $n . '/' . basename($f));
        }
        if (is_dir($vdir) && count(scandir($vdir) ?: array()) === 2) {
            rmdir($vdir);
        }
    }
    if (is_file($active_file)) {
        customcss_legacy_move($active_file, $legacy, 'active_theme.txt');
    }
    if (is_file($file)) {
        copy($file, is_dir($legacy) ? $legacy . '/settings.json' : $file . '.old');
    }
    customcss_save_settings($new);
    if (is_dir($legacy)) {
        $messages[] = 'The old files and their version history were moved to ' . $legacy . '.';
    }
    return $messages;
}

// ---------------------------------------------------------------------------
// Export and import of themes as .zip archives (since 1.22). Needs the PHP zip extension.
//   export: <theme>.zip with the folder <theme>/ and all its CSS files; "all themes" = one
//           folder per theme
//   import: all *.css files of the archive (any folder; names cleaned like file names in the
//           editor). When main.css, light-mode.css or dark-mode.css is missing or named
//           differently, the options page asks which file is which (or creates empty files).
// Pending imports wait in <data folder>/import/<token>.json until they are confirmed.
// ---------------------------------------------------------------------------

const CUSTOMCSS_IMPORT_MAX_TOTAL = 5242880; // 5 MB of CSS in one archive
const CUSTOMCSS_IMPORT_MAX_FILE  = 1048576; // 1 MB per CSS file

function customcss_zip_available()
{
    return class_exists('ZipArchive');
}

// Temporary file for a new archive (ResourceSpace's temp folder when available)
function customcss_temp_file($prefix)
{
    $dir = function_exists('get_temp_dir') ? (string) get_temp_dir() : sys_get_temp_dir();
    $f   = tempnam($dir, $prefix);
    return is_string($f) ? $f : '';
}

// Builds a zip of the given themes (each as a folder). Returns the path of the archive or ''.
function customcss_export_zip(array $themes)
{
    if (!customcss_zip_available()) {
        return '';
    }
    $file = customcss_temp_file('ccexp');
    $zip  = new ZipArchive();
    if ($file === '' || $zip->open($file, ZipArchive::OVERWRITE) !== true) {
        return '';
    }
    foreach ($themes as $theme) {
        if (!customcss_theme_exists($theme)) {
            continue;
        }
        $zip->addEmptyDir($theme);
        foreach (customcss_read_theme($theme) as $name => $css) {
            $zip->addFromString($theme . '/' . $name . '.css', $css);
        }
    }
    return $zip->close() ? $file : '';
}

// Sends the archive to the browser and deletes the temporary file
function customcss_send_zip($file, $download_name)
{
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $download_name) . '"');
    header('Content-Length: ' . (string) filesize($file));
    header('Cache-Control: no-store');
    readfile($file);
    unlink($file);
}

// Reads the CSS files of an uploaded archive.
// Returns array(files => [name => css], theme => suggested theme name) or null (+ $error).
function customcss_import_read($zipfile, $original_name, &$error)
{
    $error = '';
    if (!customcss_zip_available()) {
        $error = 'The PHP zip extension is missing - themes cannot be imported.';
        return null;
    }
    $zip = new ZipArchive();
    if (!is_file($zipfile) || $zip->open($zipfile) !== true) {
        $error = 'The file is not a valid .zip archive.';
        return null;
    }
    $files = array();
    $tops  = array();
    $total = 0;
    for ($i = 0; $i < $zip->numFiles && $i < 2000; $i++) {
        $st   = $zip->statIndex($i);
        $path = str_replace('\\', '/', (string) ($st['name'] ?? ''));
        $base = basename($path);
        if (substr($path, -1) === '/' || strpos($path, '__MACOSX/') !== false || $base === '' || $base[0] === '.'
            || strtolower(substr($base, -4)) !== '.css') {
            continue;
        }
        $name = customcss_slug(substr($base, 0, -4));
        if ($name === '' || isset($files[$name])) {
            continue; // first file with a name wins
        }
        $size = (int) ($st['size'] ?? 0);
        if ($size > CUSTOMCSS_IMPORT_MAX_FILE || ($total += $size) > CUSTOMCSS_IMPORT_MAX_TOTAL) {
            $zip->close();
            $error = 'The archive is too large (at most 1 MB per CSS file and 5 MB in total).';
            return null;
        }
        $css = $zip->getFromIndex($i);
        if (!is_string($css)) {
            continue;
        }
        if (strncmp($css, "\xEF\xBB\xBF", 3) === 0) {
            $css = substr($css, 3); // UTF-8 byte order mark
        }
        $files[$name] = str_replace("\r\n", "\n", $css);
        $parts        = explode('/', trim($path, '/'));
        $tops[count($parts) > 1 ? $parts[0] : ''] = true;
    }
    $zip->close();
    if (!$files) {
        $error = 'The archive does not contain any .css files.';
        return null;
    }
    if (count($files) > 30) {
        $error = 'The archive contains more than 30 CSS files (a theme can have at most 30 files).';
        return null;
    }
    // Suggested theme name: the common top folder, else the name of the archive
    $top   = count($tops) === 1 ? (string) key($tops) : '';
    $theme = customcss_slug($top !== '' ? $top : preg_replace('/\.zip$/i', '', basename((string) $original_name)));
    return array('files' => $files, 'theme' => $theme !== '' ? $theme : 'imported-theme');
}

// Which archive file most likely is main / light-mode / dark-mode: role => name or ''
function customcss_import_guess(array $names)
{
    $out  = array('main' => '', 'light-mode' => '', 'dark-mode' => '');
    $used = array();
    foreach (array('light-mode' => '/light/', 'dark-mode' => '/dark/', 'main' => '/main|common|base|style|theme|shared/') as $role => $re) {
        if (in_array($role, $names, true)) {
            $out[$role] = $role;
            $used[$role] = true;
            continue;
        }
        foreach ($names as $n) {
            if (!isset($used[$n]) && preg_match($re, $n)) {
                $out[$role] = $n;
                $used[$n]   = true;
                break;
            }
        }
    }
    // A single remaining file without a role becomes main.css
    $left = array_values(array_diff($names, array_keys($used)));
    if ($out['main'] === '' && count($left) === 1) {
        $out['main'] = $left[0];
    }
    return $out;
}

// Theme files from the archive files and the chosen roles (role => archive file name or '' = create empty)
function customcss_import_files(array $files, array $map)
{
    $empty = array(
        'main'       => CUSTOMCSS_MAIN_PLACEHOLDER,
        'light-mode' => "/* light-mode.css - colours of the light mode */\n",
        'dark-mode'  => "/* dark-mode.css - colours of the dark mode */\n",
    );
    $out  = array();
    $used = array();
    foreach (CUSTOMCSS_REQUIRED as $role) {
        $src = (string) ($map[$role] ?? '');
        if ($src !== '' && isset($files[$src]) && !isset($used[$src])) {
            $out[$role] = $files[$src];
            $used[$src] = true;
        } else {
            $out[$role] = $empty[$role];
        }
    }
    foreach ($files as $name => $css) {
        if (!isset($used[$name]) && !isset($out[$name])) {
            $out[$name] = $css; // extra file, keeps its name
        }
    }
    return $out;
}

function customcss_import_dir()
{
    return customcss_dir() . '/import';
}

// Stores an import that needs the user's decision. Returns its token or ''.
function customcss_import_save(array $data)
{
    $dir = customcss_import_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return '';
    }
    foreach (glob($dir . '/*.json') ?: array() as $old) { // forgotten imports older than a day
        if (filemtime($old) < time() - 86400) {
            unlink($old);
        }
    }
    $token = bin2hex(random_bytes(8));
    return file_put_contents($dir . '/' . $token . '.json', json_encode($data), LOCK_EX) !== false ? $token : '';
}

function customcss_import_load($token)
{
    if (!is_string($token) || !preg_match('/^[0-9a-f]{16}$/', $token)) {
        return null;
    }
    $f = customcss_import_dir() . '/' . $token . '.json';
    if (!is_file($f) || !is_readable($f)) {
        return null;
    }
    $d = json_decode((string) file_get_contents($f), true);
    return (is_array($d) && isset($d['files']) && is_array($d['files'])) ? $d : null;
}

function customcss_import_drop($token)
{
    if (is_string($token) && preg_match('/^[0-9a-f]{16}$/', $token)) {
        $f = customcss_import_dir() . '/' . $token . '.json';
        if (is_file($f)) {
            unlink($f);
        }
    }
}
