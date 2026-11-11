# Custom CSS – documentation

**English** | [Polski](README.pl.md)

Documentation for plugin version **1.14** (ResourceSpace 11).
*(since X.YY)* marks the plugin version in which a feature first appeared. Features without
a mark have been available since 1.00. Change history (in Polish): [CHANGELOG.md](CHANGELOG.md).

The plugin manages the look of ResourceSpace. It stores themes, which are sets of CSS files
loaded on every page of the site (including the login page). You can edit them in the browser,
go back to earlier versions and give users a button that switches between light and dark mode.

**Where:** System > Plugin Manager > Custom CSS > **Options**. Only administrators
(permission `a`) can open this page.

## Contents

1. [How it works](#how-it-works)
2. [The custom-blue theme](#the-custom-blue-theme)
3. [Options page](#options-page)
4. [Upgrading from older versions](#upgrading-from-older-versions)
5. [Rules for editing themes](#rules-for-editing-themes)
6. [Common changes – where to look](#common-changes--where-to-look)
7. [Installation and files on the server](#installation-and-files-on-the-server)
8. [License](#license-since-109)

---

## How it works

**A theme is a folder with at least three CSS files** *(since 1.13)*:

| File | When it is loaded | What it contains |
|---|---|---|
| `main.css` | always, first | everything that is the same in light and dark mode: shapes, sizes, fonts, login page, banner |
| `<extra>.css` | always, after `main.css` (A–Z) | optional files added in the editor, e.g. `print.css`, `home.css` |
| `light-mode.css` | in light mode, last | light mode colours |
| `dark-mode.css` | in dark mode, last | dark mode colours |

- **One theme is active** on the site. The file loaded later wins a conflict.
- **Which mode:** it depends on the switch settings (see
  [Light / dark mode switch](#light--dark-mode-switch)):
  - switch on: each user chooses the mode with a button,
  - switch off: everybody gets the mode set in "Mode for users who have not chosen yet".

  With "Follow the device setting" the browser itself picks `light-mode.css` or `dark-mode.css`
  according to the system setting.
- ResourceSpace 11 colours its interface with CSS variables (design tokens, e.g.
  `--colour-brand-primary-default`). The themes mainly change these variables, so changing one
  value recolours every element that uses it.
- The plugin adds the theme files at the end of `<head>`, after the ResourceSpace styles, so they
  can override them. The file URLs include their modification time (`?v=…`), so browsers fetch
  the new version right after a save.

In versions 1.00–1.12 a theme was a single CSS file, and the mode switch selected a light, a dark
and (since 1.06) a main theme separately. See
[Upgrading from older versions](#upgrading-from-older-versions).

## The custom-blue theme

The original theme shipped with the plugin *(since 1.13; before that as three themes `main-blue`,
`light-blue`, `dark-blue`)*:

| File | Content |
|---|---|
| `main.css` | shapes and sizes (buttons, fields, cards), font, login page (SSO button, texts, hiding the local login) *(since 1.05)*, hero banner *(since 1.05)*, and the rules that take their colours from variables |
| `light-mode.css` | light mode palette, brand colours, ResourceSpace tokens, colours of text, buttons and tables |
| `dark-mode.css` | dark mode palette (same sections and variable names as `light-mode.css`), plus rules for elements that ResourceSpace colours with fixed light values |

The original is stored in the plugin folder (`plugins/customcss/defaults/custom-blue/`). You can
restore it at any time: **Version history > Original themes > Restore original**.

## Options page

The editor is on the left and collapsible settings boxes are on the right.

### File tabs *(since 1.13)*

Above the editor there are tabs with the theme files: `main.css`, the extra files,
`light-mode.css` and `dark-mode.css`.

- Click a tab to edit that file. A dot "•" next to the name means unsaved changes.
- **+ Add CSS file** adds another file: type a name, and ".css" is added. Extra files are loaded
  in both modes, after `main.css`.
- **×** on an extra file removes it from the theme. It is deleted when you save; "Discard
  changes" brings it back. The three basic files cannot be removed.
- The line above the editor shows the file path and its role in the theme.

### Editor

A CodeMirror editor with syntax highlighting, line numbers, folding of `{ }` blocks and property
suggestions (shown while typing or with **Ctrl+Space**).

| Shortcut (Mac: Cmd instead of Ctrl) | Action |
|---|---|
| Ctrl+S | Save all files (new version) |
| Ctrl+Z | Undo |
| Ctrl+Y / Ctrl+Shift+Z | Redo |
| Ctrl+Space | Suggestions |
| Ctrl+/ | Toggle comment |
| Tab | Indent (4 spaces) |

- **Undo / Redo** are also available as buttons above the editor *(since 1.13)*. Each tab has
  its own undo history, which is cleared only when the page is reloaded (e.g. after saving).
- **Syntax check** below the editor checks the file of the open tab as you type: unclosed
  brackets, comments and quotes, missing `;`, unknown properties or values. When saving, all files
  are checked; if any has errors, the page asks for confirmation.

Buttons below the editor:

| Button | Action |
|---|---|
| **Save all files (new version)** *(since 1.13)* | Writes **all** files of the theme at once (also unchanged ones) and stores them together as the next version with date and time (e.g. v4). |
| **Discard changes** *(since 1.13)* | Returns all files to the last saved version: reverts changes, removes added files and brings back removed ones. Undo still works in each file afterwards. |
| **Preview** | Shows a page of the site below the editor with the editor content (`main.css` + extra files + the mode file chosen next to the address: Light mode / Dark mode), without saving. It updates as you type. |
| **Exit** | Returns to the Plugin Manager (asks for confirmation if there are unsaved changes). |

Versions 1.00–1.12 had Save, Save & Exit and Save as new version (since 1.03), for one file.

### Themes

- **Theme** – the theme to edit (asks for confirmation if there are unsaved changes). "(active)"
  marks the theme active on the site.
- **Activate** / **Deactivate** – makes the theme active, or turns themes off on the site. The
  saved theme is activated; unsaved editor changes are not used.
- **Delete theme** – deletes the theme folder. Its version history is kept.
- **Add a new theme > Save as new theme** – saves all files in the editor (all tabs) as a new
  theme (lowercase letters, numbers, `-`, `_`, max. 40 characters). It starts with version v1.
- **Active on the site** – the active theme, the switch state and the default mode.

### Editor

Editor colours: Auto (follows the page), Light, Dark blue, Dracula, Monokai, Nord. The choice
is remembered in the browser.

### Version history

- Every save creates a new version that contains **all files of the theme**, e.g.
  `v4_20261111_143012/` *(since 1.13; before that each file had its own history)*. The last 30
  versions are kept.
- **Load into editor** – loads all files of the selected version into the tabs. They become
  current only after **Save**, as a new version.
- **Delete this version** *(since 1.03)* – deletes the selected version.
- **Delete all history** *(since 1.03)* – deletes the whole history of the theme except the
  newest version.
- **Original themes > Restore original** – replaces all files of the theme with the original
  shipped with the plugin (or recreates the theme if it was deleted) and stores it as a new
  version.

### Light / dark mode switch

| Field | Meaning |
|---|---|
| **Enable the switch** | Every page shows a round button (bottom right) that each user can use to switch between light and dark mode. The choice is remembered in a browser cookie for one year. |
| **Mode for users who have not chosen yet** | Default mode: follow the device setting, light or dark. While the switch is off, it applies to all users *(since 1.13)*. |

The fields "Main theme", "Light mode theme" and "Dark mode theme" of versions 1.00–1.12 were
removed *(since 1.13)*: the mode files are now part of the theme.

## Upgrading from older versions

Version 1.13 converts the old layout (one CSS file per theme) by itself, when any page of the site
or the options page is opened after the update:

- The themes selected in the switch, by default `main-blue` + `light-blue` + `dark-blue`, become
  the theme **`custom-blue`**, keeping your edits. If other themes were selected, the theme
  `migrated-switch` is created.
- Every other old theme `X.css` becomes the theme `X`: its content goes to `main.css`, and
  `light-mode.css` and `dark-mode.css` are empty.
- The active theme, the switch state and the default mode are kept.
- The old files and their version history are moved to `Custom/legacy-<date>/`. Nothing is
  deleted.
- The options page shows what was converted.

## Rules for editing themes

1. **Preview first, then Save.** The preview saves nothing. To check both modes, switch Light
   mode / Dark mode next to the preview address.
2. **Change variable values, not rules.** Each file has two parts *(since 1.05)*:
   - **Part A – variables** (`:root { … }` blocks): colours, sizes, texts,
   - **Part B – rules** that use them.
3. **The right file for a change:**
   - for both modes (shape, size, font, login page, banner) → `main.css`,
   - colours of one mode only → `light-mode.css` or `dark-mode.css`,
   - a larger, separate part (e.g. the home page look) → an extra file.
4. **The same colour in both modes.** `light-mode.css` and `dark-mode.css` have the same sections
   and variable names. If you change e.g. `--cc-table-head-bg`, do it in both files.
5. **Add your own rules at the end**, in the "Extra rules" / "Your own rules" section. For
   elements that stay light in dark mode, inspect them in the browser (F12), copy the selector and
   add a rule to `dark-mode.css`.
6. **Keep your own variants in a copy of the theme** (**Save as new theme**, e.g.
   `custom-green`). The original `custom-blue` can always be restored.
7. **Fonts from other servers do not work.** The security policy (CSP) blocks fonts from
   external addresses. Use system fonts or put the font files on the server.
8. **Write colours with `#`**, e.g. `#2563EB`.

## Common changes – where to look

The paths refer to the `custom-blue` theme. Section numbers are listed in the comments at the top
of each file.

### Main colour (buttons, active elements)

`light-mode.css` and `dark-mode.css`, section 2:

```css
--colour-brand-primary-default: #2563EB; /* main colour */
--colour-brand-primary-hover:   #3B82F6; /* on hover */
--colour-brand-primary-dark:    #1D4ED8; /* when pressed */
```

### Background of the page, header, cards

`light-mode.css`, section 3 (`--colour-surface-*`). In `dark-mode.css` it is easiest to change the
palette in section 1 (`--dk-0` … `--dk-5`, `--dk-text*`), because the other values derive from it.

### Shape of buttons and fields

`main.css`, section 1:

```css
--cc-cta-radius:   5px;  /* 0 = square, 999px = pill */
--cc-cta-weight:   600;  /* label weight */
--cc-field-radius: 5px;  /* form fields */
--cc-card-radius:  8px;  /* cards and panels */
```

### Font

`main.css`, section 2: `--cc-font`.

### Login page – SSO button and texts *(since 1.05)*

`main.css`, section 3. The SSO button is centred and as wide as the login fields.

```css
--cc-login-sso-title: "Single sign-on";   /* title above the button */
--cc-login-sso-text:  "Log in with ...";  /* text under the title */
--cc-login-sso-note:  "Problems ...";     /* short note below the button */
```

- `""` gives an empty text.
- `none` in `--cc-login-sso-title` removes the title together with the text.
- `none` in `--cc-login-sso-note` removes the note.
- New line in a text: `\A`, e.g. `"First line\ASecond line"`.
- Colours of the title and texts: `light-mode.css` / `dark-mode.css`, section 4.4.

### Hiding the local account login *(since 1.05)*

`main.css`, section 10.1. Change `@media not all {` (fields shown, default) to `@media all {`
(fields hidden).

- This hides the user name and password fields, "Keep me logged in", the "Log in" button and the
  "Request account" and "Forgotten password" links.
- The fields are hidden **only when an SSO button is on the page**. If SSO is switched off,
  nobody loses the ability to log in.
- Hiding is visual only. Local login still works, so this is not a security measure.

### Height of the hero banner (home page slideshow) *(since 1.05)*

`main.css`, section 4. The slideshow images are set in ResourceSpace (System > Slideshow).

```css
--cc-hero-height-desktop: 420px;      /* e.g. 300px, 50vh; 100vh = whole screen */
--cc-hero-height-mobile:  240px;      /* screens narrower than 820px */
--cc-hero-position:       center top; /* which part of the image stays visible */
--cc-hero-content-offset: 0px;        /* space above the content */
```

The mobile breakpoint (819px) is written in the `@media` rule in section 11, because media
queries cannot use variables.

## Installation and files on the server

### From a zip archive *(since 1.10)*

The plugin is distributed on its own, as a `customcss-<version>.zip` archive (download from
<https://github.com/s29829/customcss/releases>, since 1.11). The archive contains the folder
`customcss/` with the code, license and documentation.

1. Download `customcss-<version>.zip` and, optionally, check the checksum:
   `sha256sum -c customcss-<version>.zip.sha256`.
   The web server needs write access to `<RS_ROOT>/Custom`:
   `sudo install -d -o www-data -g www-data <RS_ROOT>/Custom`.
2. Install it in one of these ways:
   - with **Plugin Admin**: Upload a plugin > choose the file > Upload (when updating:
     **Overwrite**),
   - with the script attached to the release *(since 1.12)*: `bash rs-plugin-zip-install.sh customcss-<version>.zip`.
     It checks the checksum and the PHP syntax, replaces the whole plugin folder, sets the
     permissions and restarts PHP-FPM. Another ResourceSpace path: `RS_ROOT=/path bash …`,
   - by hand on the server (when updating, delete the old `customcss` folder first; the plugin's
     data is stored outside it):
     ```bash
     sudo unzip customcss-<version>.zip -d <RS_ROOT>/plugins/
     sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
     ```
3. System > Plugin Manager, activate "Custom CSS" and click **Options**.

When the options page is opened for the first time, the plugin creates `Custom/themes` and the
missing original themes *(since 1.10)*. A theme that has a version history (deleted on purpose)
is not recreated. If the folder cannot be created, the page explains how to grant access.
Finally, select a theme and click **Activate**.

### With git *(since 1.11)*

The repository <https://github.com/s29829/customcss> contains the complete plugin folder:

```bash
sudo git clone https://github.com/s29829/customcss <RS_ROOT>/plugins/customcss
sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
```

Update: `sudo git -C <RS_ROOT>/plugins/customcss pull`.

### With an installer script (console)

`bash customcss-install.sh` (installer with the files built in) or `bash customcss-github-install.sh`
(downloads the plugin from <https://github.com/s29829/customcss>, no token needed).

- All installation methods replace only the plugin files *(since 1.13)*. The themes in
  `Custom/themes` (also your edits of `custom-blue`), the history and the settings are kept.
- A new version of the original goes to `defaults/`; restore it with **Restore original**.
- In versions 1.00–1.12 the installers overwrote the original themes in `Custom/themes` and
  stored their previous content in the history.

| Path | Content |
|---|---|
| `<RS_ROOT>/Custom/themes/<theme>/` | Theme files: `main.css`, `light-mode.css`, `dark-mode.css`, extra `*.css` |
| `<RS_ROOT>/Custom/versions/<theme>/v<N>_<date>_<time>/` | Theme versions (all files) |
| `<RS_ROOT>/Custom/settings.json` | Active theme, switch, default mode |
| `<RS_ROOT>/Custom/legacy-<date>/` | Files of older plugin versions (after the conversion) |
| `<RS_ROOT>/plugins/customcss/defaults/<theme>/` | Original themes (for "Restore original") |
| `<RS_ROOT>/plugins/customcss/lib/codemirror/` | Editor libraries (CodeMirror 5) |

The web server (`www-data`) needs write access to `Custom/`. The installers set this up. Since
1.04 the plugin also works when installed in `filestore/plugins`.

## License *(since 1.09)*

- The plugin is licensed under the **PolyForm Noncommercial License 1.0.0** (file
  [LICENSE](LICENSE)).
- It is **free** for private and noncommercial use, and for noncommercial organizations
  (education, public institutions, charities). You may change and share it for these purposes,
  keeping the license text.
- **Commercial use** (a company, services for clients, a paid product) requires the author's
  written permission: <https://github.com/s29829>. Details: [LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md).
- The CodeMirror 5 library in `lib/codemirror/` has its own MIT license (file `lib/codemirror/LICENSE`).
