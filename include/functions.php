<?php
// Helper functions shared by the hook and the setup page.
// Layout on disk:
//   <ResourceSpace root>/Custom/themes/<name>.css       one file per theme
//   <ResourceSpace root>/Custom/versions/<name>/*.css   previous versions of each theme (v<N>_<date>_<time>.css)
//   <ResourceSpace root>/Custom/versions/<name>/current_version.txt   current version number of the theme
//   <ResourceSpace root>/Custom/active_theme.txt        theme loaded while the mode switch is off
//   <ResourceSpace root>/Custom/settings.json           light/dark mode switch settings
//   plugins/customcss/defaults/<name>.css               original themes ("Restore original")

function customcss_dir()
{
    return dirname(__FILE__, 4) . '/Custom';
}

function customcss_defaults_dir()
{
    return dirname(__FILE__, 2) . '/defaults';
}

function customcss_versions_dir($slug)
{
    return customcss_dir() . '/versions/' . $slug;
}

// Converts any text to a safe theme name: a-z, 0-9, "-" and "_" (max 40 chars)
function customcss_slug($name)
{
    $name = strtolower(trim((string) $name));
    $name = preg_replace('/[^a-z0-9_-]+/', '-', $name);
    return substr(trim($name, '-_'), 0, 40);
}

// Names of all existing themes
function customcss_list()
{
    $out = array();
    foreach (glob(customcss_dir() . '/themes/*.css') ?: array() as $f) {
        $slug = basename($f, '.css');
        if ($slug === customcss_slug($slug)) {
            $out[] = $slug;
        }
    }
    sort($out);
    return $out;
}

// Names of the original themes shipped with the plugin
function customcss_defaults()
{
    $out = array();
    foreach (glob(customcss_defaults_dir() . '/*.css') ?: array() as $f) {
        $slug = basename($f, '.css');
        if ($slug === customcss_slug($slug)) {
            $out[] = $slug;
        }
    }
    sort($out);
    return $out;
}

function customcss_theme_exists($slug)
{
    return $slug !== '' && is_file(customcss_dir() . '/themes/' . $slug . '.css');
}

// Active theme (used while the mode switch is off), '' when none
function customcss_active()
{
    $f = customcss_dir() . '/active_theme.txt';
    if (!is_readable($f)) {
        return '';
    }
    $slug = customcss_slug((string) file_get_contents($f));
    return customcss_theme_exists($slug) ? $slug : '';
}

// Sets the active theme ('' = deactivate)
function customcss_set_active($slug)
{
    $f = customcss_dir() . '/active_theme.txt';
    if ($slug === '') {
        return !is_file($f) || @unlink($f);
    }
    return file_put_contents($f, $slug, LOCK_EX) !== false;
}

// Light/dark mode switch settings
// main_theme = shared theme loaded BEFORE the light/dark theme (or the single active theme), '' = none
function customcss_settings()
{
    $s = array('switch' => false, 'main_theme' => 'main-blue', 'light_theme' => 'light-blue', 'dark_theme' => 'dark-blue', 'default_mode' => 'auto');
    $f = customcss_dir() . '/settings.json';
    if (is_readable($f)) {
        $json = json_decode((string) file_get_contents($f), true);
        if (is_array($json)) {
            $s = array_merge($s, array_intersect_key($json, $s));
        }
    }
    $s['switch']      = !empty($s['switch']);
    $s['main_theme']  = customcss_slug((string) $s['main_theme']);
    $s['light_theme'] = customcss_slug($s['light_theme']);
    $s['dark_theme']  = customcss_slug($s['dark_theme']);
    if (!in_array($s['default_mode'], array('auto', 'light', 'dark'), true)) {
        $s['default_mode'] = 'auto';
    }
    return $s;
}

function customcss_save_settings($s)
{
    $dir = customcss_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    return file_put_contents($dir . '/settings.json', json_encode($s, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// ---- Version history ----
// File name of a version: v<N>_<YYYYMMDD>_<HHMMSS>[_k].css
//   N = version number (raised by "Save as new version"), followed by the date and time it was stored.
//   Older files without the "v<N>_" prefix count as version 1.
// Every normal Save stores the previous content under the current version number.

function customcss_version_valid($name)
{
    return (bool) preg_match('/^(v\d+_)?\d{8}_\d{6}(_\d+)?$/', (string) $name);
}

// Splits a version name into number, time stamp and sequence (for several saves in one second)
function customcss_version_parse($name)
{
    if (!preg_match('/^(?:v(\d+)_)?(\d{8}_\d{6})(?:_(\d+))?$/', (string) $name, $m)) {
        return null;
    }
    return array(
        'num'   => (isset($m[1]) && $m[1] !== '') ? (int) $m[1] : 1,
        'stamp' => $m[2],
        'seq'   => isset($m[3]) ? (int) $m[3] : 0,
    );
}

// Version names of a theme in chronological order (oldest first)
function customcss_versions($slug)
{
    $out = array();
    foreach (glob(customcss_versions_dir($slug) . '/*.css') ?: array() as $f) {
        $n = basename($f, '.css');
        if (customcss_version_valid($n)) {
            $out[] = $n;
        }
    }
    usort($out, function ($a, $b) {
        $pa = customcss_version_parse($a);
        $pb = customcss_version_parse($b);
        return array($pa['stamp'], $pa['seq'], $pa['num']) <=> array($pb['stamp'], $pb['seq'], $pb['num']);
    });
    return $out;
}

// Current version number of a theme (1 when nothing was stored yet)
function customcss_current_version($slug)
{
    $num = 1;
    $f   = customcss_versions_dir($slug) . '/current_version.txt';
    if (is_readable($f)) {
        $num = max($num, (int) trim((string) file_get_contents($f)));
    }
    foreach (customcss_versions($slug) as $v) {
        $num = max($num, customcss_version_parse($v)['num']);
    }
    return $num;
}

function customcss_set_current_version($slug, $num)
{
    $dir = customcss_versions_dir($slug);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    return file_put_contents($dir . '/current_version.txt', (string) (int) $num, LOCK_EX) !== false;
}

// Human readable label of a version, e.g. "v2 - 2026-11-07 21:30:12 (1.2 KB)"
function customcss_version_label($slug, $name)
{
    $p    = customcss_version_parse($name);
    $dt   = $p ? DateTime::createFromFormat('Ymd_His', $p['stamp']) : false;
    $file = customcss_versions_dir($slug) . '/' . $name . '.css';
    $size = is_file($file) ? filesize($file) : 0;
    return ($p ? 'v' . $p['num'] . ' - ' : '') . ($dt ? $dt->format('Y-m-d H:i:s') : $name)
        . ' (' . number_format($size / 1024, 1) . ' KB)';
}

// Stores the CURRENT content of a theme in its version history (under the current version number,
// or $num when given) and keeps the last $keep entries. Returns the stored name, '' when nothing
// had to be stored, or false on error.
function customcss_snapshot($slug, $keep = 30, $num = null)
{
    $src = customcss_dir() . '/themes/' . $slug . '.css';
    if ($slug === '' || !is_file($src)) {
        return '';
    }
    $dir = customcss_versions_dir($slug);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $num      = $num === null ? customcss_current_version($slug) : (int) $num;
    $versions = customcss_versions($slug);

    // Skip a duplicate of the newest entry of the same version
    $last = end($versions);
    if ($last !== false && customcss_version_parse($last)['num'] === $num
        && file_get_contents($dir . '/' . $last . '.css') === file_get_contents($src)) {
        return '';
    }

    $stamp = 'v' . $num . '_' . date('Ymd_His');
    $name  = $stamp;
    $i     = 1;
    while (is_file($dir . '/' . $name . '.css')) {
        $name = $stamp . '_' . $i;
        $i++;
    }
    if (!@copy($src, $dir . '/' . $name . '.css')) {
        return false;
    }
    $versions = customcss_versions($slug);
    foreach (array_slice($versions, 0, max(0, count($versions) - $keep)) as $old) {
        @unlink($dir . '/' . $old . '.css');
    }
    return $name;
}

// Saves $content to the theme and stores it in the history as the next version number.
// Returns the name of the new version file, or false on error.
function customcss_save_new_version($slug, $content)
{
    if (!customcss_write_theme($slug, $content)) {
        return false;
    }
    $num = customcss_current_version($slug) + 1;
    if (!customcss_set_current_version($slug, $num)) {
        return false;
    }
    $name = customcss_snapshot($slug, 30, $num);
    return is_string($name) && $name !== '' ? $name : false;
}

// Deletes all stored files of version number $num. Returns the number of deleted files.
function customcss_delete_version($slug, $num)
{
    $count = 0;
    foreach (customcss_versions($slug) as $v) {
        if (customcss_version_parse($v)['num'] === (int) $num && @unlink(customcss_versions_dir($slug) . '/' . $v . '.css')) {
            $count++;
        }
    }
    return $count;
}

// Deletes the whole history except the newest entry. Returns the number of deleted files.
function customcss_clear_history($slug)
{
    $versions = customcss_versions($slug);
    array_pop($versions);
    $count = 0;
    foreach ($versions as $v) {
        if (@unlink(customcss_versions_dir($slug) . '/' . $v . '.css')) {
            $count++;
        }
    }
    return $count;
}

// Writes a theme. The previous content goes to the version history first.
function customcss_write_theme($slug, $content)
{
    if ($slug === '') {
        return false;
    }
    $dir = customcss_dir() . '/themes';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $file = $dir . '/' . $slug . '.css';
    if (is_file($file)) {
        if (file_get_contents($file) === $content) {
            return true; // nothing changed, no new version
        }
        if (customcss_snapshot($slug) === false) {
            return false;
        }
    }
    return file_put_contents($file, $content, LOCK_EX) !== false;
}

// Replaces a theme with its original (the current content is kept in the version history)
function customcss_restore_default($slug)
{
    $src = customcss_defaults_dir() . '/' . $slug . '.css';
    if ($slug === '' || !is_readable($src)) {
        return false;
    }
    return customcss_write_theme($slug, (string) file_get_contents($src));
}

// Creates <ResourceSpace root>/Custom/themes when it is missing. Returns '' or an error message.
// (No "@": ResourceSpace shows its error page also for silenced warnings, so check first.)
function customcss_prepare_dirs()
{
    $base = customcss_dir();
    $dir  = $base . '/themes';
    if (is_dir($dir)) {
        return is_writable($dir) ? '' : 'The web server cannot write to ' . $dir . ' - themes cannot be saved.';
    }
    $parent = is_dir($base) ? $base : dirname($base);
    if (!is_writable($parent)) {
        return 'The folder ' . $dir . ' does not exist and the web server cannot create it. '
            . 'Create it and give the web server write access (e.g. chown -R www-data:www-data ' . $base . ').';
    }
    return mkdir($dir, 0755, true) ? '' : 'Could not create ' . $dir . '.';
}

// Copies the original themes that do not exist yet to Custom/themes - needed after installing
// the plugin from a .zip archive (no installer copies them). A theme that has a version history
// was deleted on purpose and is not recreated. Returns the names of the created themes.
function customcss_install_defaults()
{
    $dir     = customcss_dir() . '/themes';
    $created = array();
    if (!is_dir($dir) || !is_writable($dir)) {
        return $created;
    }
    foreach (customcss_defaults() as $slug) {
        $src = customcss_defaults_dir() . '/' . $slug . '.css';
        if (customcss_theme_exists($slug) || is_dir(customcss_versions_dir($slug)) || !is_readable($src)) {
            continue;
        }
        if (file_put_contents($dir . '/' . $slug . '.css', (string) file_get_contents($src), LOCK_EX) !== false) {
            $created[] = $slug;
        }
    }
    return $created;
}
