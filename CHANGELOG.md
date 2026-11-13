# Changelog - Custom CSS

Plugin version numbers: 1.00, 1.01, 1.02 … (+0.01 for every change). The current version is in `customcss.yaml`.

## [1.24] - 2026-11-13
- Documentation: English is the main language - change history translated to English.

## [1.23] - 2026-11-13
- Documentation: installation on Apache (Ubuntu/Debian and the RHEL family) – user and group that run PHP (mod_php / PHP-FPM), permissions, no directory listings (Options -Indexes), SELinux.

## [1.22] - 2026-11-12
- Editor colours (Auto) change at once when the light/dark switch is clicked (event customcss:mode); extra CSS files are deleted on the server at once (× on the tab or Delete file) as a new version; outline buttons Add CSS file, Load original, Delete file, Undo, Redo, new Undo/Redo icons; "Theme folder" and the text "Save all files… as a new theme" removed from the Themes box; download of a theme / all themes as a zip and import of a theme from a zip, with a choice of the main / light-mode / dark-mode files or empty ones.

## [1.21] - 2026-11-12
- Documentation: installation links point to the repository s29829/custom-css (the plugin folder is still customcss).

## [1.20] - 2026-11-12
- Documentation: new names of the other plugins; the ResourceSpace plugins page described as System > Plugins.

## [1.19] - 2026-11-11
- Clean-up of the change history (no change in the plugin).

## [1.18] - 2026-11-11
- Documentation: installation with Plugin Manager or on the command line (curl, unzip, chown) without bash scripts; releases contain only the zip archive.

## [1.17] - 2026-11-11
- The light/dark switch works without an active theme: the button is shown whenever the switch is enabled; for logged-in users it stores the ResourceSpace appearance preference (user_pref_appearance: light/dark/device – new page pages/mode.php) and reloads the ResourceSpace mode styles at once (css_override.php); with an active theme it also switches light-mode.css/dark-mode.css; the button state follows the user's preference.

## [1.16] - 2026-11-11
- Plugin data in <filestore>/customcss: themes in themes/ (served through $storageurl), settings and version history in the non-public data_<key>/; automatic move from <RS_ROOT>/Custom (the old folder stays as Custom-moved-<date>); the plugin creates its folders itself.
- "Reset plugin" (confirmed with the word RESET) deletes all themes, the history and the settings; the original themes are created again.

## [1.15] - 2026-11-11
- Repair after the conversion of old themes: an empty main.css of the custom-blue theme (created by 1.13–1.14 when the server had a version older than 1.06) is replaced with the original automatically – the full-width SSO button with the texts above and below, hiding the local login and the hero banner height settings are back; the conversion now uses the original main.css.
- Stronger SSO button rules in main.css (old rules in light-mode.css / dark-mode.css no longer override them); the "Load original" button above the editor loads the original of the current theme file (can be undone).

## [1.14] - 2026-11-11
- Themes group: light-mode, dark-mode, main.

## [1.13] - 2026-11-11
- New theme model: a theme is a folder with main.css, light-mode.css, dark-mode.css and any extra CSS files; the themes main-blue, light-blue and dark-blue merged into one theme custom-blue.
- Editor: file tabs above the editor (switching, adding and deleting extra files), "Save all files (new version)" stores all files together as a new version with date and time, "Discard changes", Undo/Redo buttons (separate history per tab), preview with a choice of light/dark mode.
- Mode switch: "Enable the switch" and "Mode for users who have not chosen yet" remain (with the switch off: the mode for everyone); the choices of the main, light and dark theme removed.
- Automatic conversion of the old layout (files Custom/themes/*.css) into theme folders, keeping changes, the active theme and the settings; old files in Custom/legacy-<date>/.

## [1.12] - 2026-11-11
- Changes in the documentation and release tools (no change in the plugin).

## [1.11] - 2026-11-11
- Changes in the documentation and release tools (no change in the plugin).

## [1.10] - 2026-11-11
- Self-contained zip package: LICENSE-COMMERCIAL.md in the plugin folder, license and documentation in the package; installation instructions for the zip archive.
- The first opening of the options page creates the folder Custom/themes (or shows how to grant permissions) and missing original themes – installing from the zip needs no installer; a theme with version history (deleted on purpose) is not recreated.

## [1.09] - 2026-11-11
- License PolyForm Noncommercial 1.0.0 (file LICENSE; commercial use only with the author's written permission) and the MIT license file of the bundled CodeMirror library; "License" section in the documentation.

## [1.08] - 2026-11-11
- Documentation: neutral example theme name (dark-blue-custom).

## [1.07] - 2026-11-11
- Documentation in the plugin folder: README.md (EN) and README.pl.md (PL), with the version in which each feature appeared.

## [1.06] - 2026-11-11
- New theme main-blue (common part): shapes, sizes, font, rules for buttons/fields/tables, login page (SSO, hiding the local login) and hero banner; light-blue and dark-blue now contain only colours and rules that depend on light/dark mode.
- Light/dark switch: choice of a "Main theme" (default main-blue, can be switched off) – loaded before the light/dark theme and before a single active theme; the switch button does not remove it, the preview keeps it.

## [1.05] - 2026-11-11
- Themes light-blue and dark-blue: the same section layout (part A – variables, part B – rules) and the same --cc-* variables in both files; dark-blue now also uses the common rules for buttons, fields, tables and font.
- Login page: switch to hide the local login fields (only when there is an SSO button), SSO button centred and as wide as the fields, title and text above the button and a short description below it (variables --cc-login-sso-*).
- Home page: slider as a hero banner with heights for desktop and mobile (variables --cc-hero-*, breakpoint 819px).

## [1.04] - 2026-11-09
- Follows the ResourceSpace guidelines ("Writing your own plugins"): folder `CustomCSS` → `customcss` (folder name = YAML file name, same layout as on the server).
- The URL of the CodeMirror libraries is built with `get_plugin_path()` (also works for a plugin in `filestore/plugins`).

## [1.03] - 2026-11-08
- "Save as new version" button below the editor – stores the theme and creates the next version number (file name: version number + date and time).
- "Delete this version" (deletes all files of the selected version) and "Delete all history" (deletes the whole history except the newest entry).

## [1.02] - 2026-11-08
- The version history shows the version number and date in chronological order (oldest first). New version files: `v<N>_<YYYYMMDD>_<HHMMSS>.css`; older files without the prefix are treated as v1.

## [1.01] - 2026-11-08
- Options page: plugin title above the breadcrumbs, like the ResourceSpace admin pages.
- "Settings" heading removed; collapsible sections in the right column (Themes – open by default, Editor, Version history, Light / dark mode switch).

## [1.00] - 2026-11-08
- Import of the existing plugin version: CSS theme editor (CodeMirror), themes `light-blue` / `dark-blue` in `Custom/themes`, version history, light / dark mode switch.
