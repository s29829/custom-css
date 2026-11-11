# Custom CSS – documentation

**English** | [Polski](README.pl.md)

Documentation for plugin version **1.12** (ResourceSpace 11).
*(since X.YY)* marks the plugin version in which a feature first appeared. Features without
a mark have been available since 1.00. Change history (in Polish): [CHANGELOG.md](CHANGELOG.md).

The plugin manages the look of ResourceSpace. It stores themes, which are CSS files loaded on
every page of the site (including the login page). You can edit them in the browser, restore
older versions and give users a button that switches between light and dark mode.

**Where:** System > Plugin Manager > Custom CSS > **Options**. Only administrators
(permission `a`) can open this page.

## Contents

1. [How it works](#how-it-works)
2. [Themes shipped with the plugin](#themes-shipped-with-the-plugin)
3. [Options page](#options-page)
4. [Rules for editing themes](#rules-for-editing-themes)
5. [Common changes – where to look](#common-changes--where-to-look)
6. [Installation and files on the server](#installation-and-files-on-the-server)

---

## How it works

- Each theme is one file: `Custom/themes/<name>.css`.
- The plugin adds the themes at the end of `<head>`, after the ResourceSpace styles
  (`global.css`, `light.css`), so they can override them.
- ResourceSpace 11 colours its interface with CSS variables (design tokens, e.g.
  `--colour-brand-primary-default`). The themes mainly change these variables, so changing one
  value recolours every element that uses it.
- Loading order (the theme loaded later wins a conflict):
  1. **main theme** – the part shared by both modes *(since 1.06)*,
  2. **the light or dark theme** (while the switch is on) or **one active theme** (while the
     switch is off).
- The file URL includes its modification time (`?v=…`), so browsers fetch the new version right
  after a save.

## Themes shipped with the plugin

| Theme | Content |
|---|---|
| `main-blue` *(since 1.06)* | The **shared** part of light and dark mode: shapes and sizes (buttons, fields, cards), font, login page (SSO button, texts, hiding the local login), the home page hero banner, and the rules that take their colours from variables. |
| `light-blue` | **Light mode colours**: palette, brand colours, ResourceSpace tokens, colours of text, buttons and tables. |
| `dark-blue` | **Dark mode colours** (same sections and variable names as `light-blue`), plus rules for elements that ResourceSpace colours with fixed light values (panels, dialogs, messages, resource status badges). |

- Since 1.06, `light-blue` and `dark-blue` contain colours only. Use them together with
  `main-blue` as the main theme.
- In versions 1.00–1.05 `light-blue` and `dark-blue` were complete, stand-alone themes.
- Since 1.05 both files have the same section layout and the same `--cc-*` variable names.

The originals are stored in the plugin folder (`plugins/customcss/defaults/`). You can restore
them at any time (see [Version history](#version-history)).

## Options page

The editor is on the left and collapsible settings boxes are on the right *(two-column layout
with collapsible boxes: since 1.01)*.

### Editor

A CodeMirror editor with syntax highlighting, line numbers and folding of `{ }` blocks.
Property suggestions appear while typing or with **Ctrl+Space**.

| Shortcut | Action |
|---|---|
| Ctrl+S / Cmd+S | Save |
| Ctrl+Space | Suggestions |
| Ctrl+/ | Toggle comment |
| Tab | Indent (4 spaces) |

**Syntax check** below the editor checks the CSS as you type. It finds unclosed brackets,
comments and quotes, missing `;`, and unknown properties or values (warnings). If there are
errors when you save, the page asks for confirmation.

Buttons below the editor:

| Button | Action |
|---|---|
| **Save** | Saves the theme. The previous content goes to the history under the current version number. |
| **Save & Exit** | Saves and returns to the Plugin Manager. |
| **Save as new version** *(since 1.03)* | Saves and starts the next version number (e.g. v3 → v4). |
| **Preview** | Shows a page of the site below the editor with the current editor content, without saving. You can change the page address (default `pages/home.php`). The preview updates as you type. |
| **Exit** | Returns to the Plugin Manager (asks for confirmation if there are unsaved changes). |

In the preview the edited theme replaces the light / dark (or active) theme, and the main theme
stays on. When you edit the main theme itself, the light / dark theme stays on *(since 1.06)*.

### Themes

- **Theme** – the theme to edit. With unsaved changes the page asks for confirmation.
  "(active)" marks the active theme used while the switch is off.
- **Save & activate** / **Deactivate** – sets or clears the single active theme (while the mode
  switch is off).
- **Delete theme** – deletes the theme file. Its version history is kept.
- **Add a new theme** – saves the current editor content under a new name (lowercase letters,
  numbers, `-`, `_`, max. 40 characters).
- **Active on the site** – what the site loads now, e.g.
  `main-blue + light/dark switch (light-blue / dark-blue)`.

### Editor

Editor colours: Auto (follows the page), Light, Dark blue, Dracula, Monokai, Nord. The choice
is remembered in the browser.

### Version history

- Every **Save** stores the previous content as `v<N>_<date>_<time>.css`. The last 30 entries
  per theme are kept.
- The list shows the version number and date, oldest first *(since 1.02)*.
- **Load into editor** – loads the selected version into the editor. It becomes current only
  after Save.
- **Delete this version** *(since 1.03)* – deletes all files with that version number.
- **Delete all history** *(since 1.03)* – deletes the whole history of the theme except the
  newest entry.
- **Original themes > Restore original** – replaces the theme with the original shipped with
  the plugin, or recreates it if it was deleted. The current content goes to the history.
  Available originals: `light-blue`, `dark-blue` and `main-blue` *(since 1.06)*.

### Light / dark mode switch

When enabled, every page shows a round button (bottom right). Each user can switch between
light and dark mode with it. The choice is remembered in a browser cookie for one year.

| Field | Meaning |
|---|---|
| **Enable the switch** | Turns the button on. The switch then replaces the single "active theme". |
| **Main theme (shared part)** *(since 1.06)* | Shared theme loaded **before** the light / dark theme. It is also loaded while the switch is off, before the single active theme. Default `main-blue`; "- none -" turns it off. It must be a different file from the light and dark themes. |
| **Light mode theme** | Theme for light mode (default `light-blue`). |
| **Dark mode theme** | Theme for dark mode (default `dark-blue`). |
| **Mode for users who have not chosen yet** | Default mode: follow the device setting, light or dark. |

The button switches only the light / dark theme. The main theme stays loaded.

## Rules for editing themes

1. **Preview first, then Save.** The preview saves nothing, so you can experiment safely.
2. **Change variable values, not rules.** Each theme has two parts *(since 1.05)*:
   - **Part A – variables** (`:root { … }` blocks). This is where you change colours, sizes
     and texts.
   - **Part B – rules** that use those variables. Usually you do not need to touch them.
3. **The right file for a change** *(since 1.06)*:
   - a change for both modes (shape, size, font, login page, banner) → `main-blue`,
   - a change to the colours of one mode only → `light-blue` or `dark-blue`.
4. **Change a colour in both modes.** `light-blue` and `dark-blue` have the same sections and
   variable names. If you change e.g. `--cc-table-head-bg`, do it in both files.
5. **Add your own rules at the end**, in the "Extra rules" / "Your own rules" section of the
   right file. For elements that stay light in dark mode, inspect them in the browser (F12),
   copy the selector and add a rule to `dark-blue`.
6. **Changes to the originals can be overwritten.** The plugin installer replaces the original
   themes with the plugin's versions (the previous content goes to the history). Keep larger
   changes in a copy (**Add a new theme**, e.g. `dark-blue-custom`) and select it in the
   switch settings.
7. **Fonts from other servers do not work.** The security policy (CSP) blocks fonts from
   external addresses. Use system fonts or put the font files on the server.
8. **Write colours with `#`**, e.g. `#2563EB`. The syntax check gives a hint if `#` is missing.

## Common changes – where to look

Section numbers refer to the themes of version 1.06 and later.

### Main colour (buttons, active elements)

`light-blue` and `dark-blue`, section 2:

```css
--colour-brand-primary-default: #2563EB; /* main colour */
--colour-brand-primary-hover:   #3B82F6; /* on hover */
--colour-brand-primary-dark:    #1D4ED8; /* when pressed */
```

### Background of the page, header, cards

`light-blue` section 3 (`--colour-surface-*`). In `dark-blue` it is easiest to change the
palette in section 1 (`--dk-0` … `--dk-5`, `--dk-text*`), because the other values derive
from it.

### Shape of buttons and fields

`main-blue`, section 1:

```css
--cc-cta-radius:   5px;  /* 0 = square, 999px = pill */
--cc-cta-weight:   600;  /* label weight */
--cc-field-radius: 5px;  /* form fields */
--cc-card-radius:  8px;  /* cards and panels */
```

### Font

`main-blue`, section 2: `--cc-font`.

### Login page – SSO button and texts *(since 1.05)*

`main-blue`, section 3. The SSO button is centred and as wide as the login fields.

```css
--cc-login-sso-title: "Single sign-on";   /* title above the button */
--cc-login-sso-text:  "Log in with ...";  /* text under the title */
--cc-login-sso-note:  "Problems ...";     /* short note below the button */
```

- `""` gives an empty text.
- `none` in `--cc-login-sso-title` removes the title together with the text.
- `none` in `--cc-login-sso-note` removes the note.
- New line in a text: `\A`, e.g. `"First line\ASecond line"`.
- The colours of the title and texts are in `light-blue` / `dark-blue`, section 4.4.

### Hiding the local account login *(since 1.05)*

`main-blue`, section 10.1. Change:

```css
@media not all {   /* fields shown (default) */
```

to

```css
@media all {       /* fields hidden */
```

This hides the user name and password fields, "Keep me logged in", the "Log in" button and the
"Request account" and "Forgotten password" links.

- The fields are hidden **only when an SSO button is on the page**. If SSO is switched off,
  nobody loses the ability to log in.
- Hiding is visual only. Local login still works, so this is not a security measure.
  An administrator can log in locally after switching back to `@media not all`.

### Height of the hero banner (home page slideshow) *(since 1.05)*

`main-blue`, section 4. The slideshow images are set in ResourceSpace (System > Slideshow).
On the home page the plugin turns the full-screen background into a banner at the top.

```css
--cc-hero-height-desktop: 420px;      /* e.g. 300px, 50vh; 100vh = whole screen */
--cc-hero-height-mobile:  240px;      /* screens narrower than 820px */
--cc-hero-position:       center top; /* which part of the image stays visible */
--cc-hero-content-offset: 0px;        /* space above the content, e.g. to start it below the banner */
```

The mobile breakpoint (819px) is written in the `@media` rule in section 11, because media
queries cannot use variables.

## Installation and files on the server

### From a zip archive *(since 1.10)*

The plugin is distributed on its own, as a `customcss-<version>.zip` archive (download from
<https://github.com/s29829/customcss/releases>, since 1.11). The archive contains the folder `customcss/` with the code, license and
documentation.

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

When the options page is opened for the first time, the plugin creates `Custom/themes` and copies
the missing original themes into it *(since 1.10)*. A theme that has a version history (deleted
on purpose) is not recreated. If the folder cannot be created, the page explains how to grant
access.

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

- The installer scripts overwrite the plugin files and the original themes. Before that they store
  their current content in the version history.
- Your own themes, the history and the settings are kept.
- An update from a zip archive does not change the existing themes in `Custom/themes`. New
  versions of the originals go to `defaults/` (restore them with **Restore original**), and
  original themes that do not exist yet are created automatically.

| Path | Content |
|---|---|
| `<RS_ROOT>/Custom/themes/<name>.css` | Themes |
| `<RS_ROOT>/Custom/versions/<name>/v<N>_<date>_<time>.css` | Version history of a theme |
| `<RS_ROOT>/Custom/versions/<name>/current_version.txt` | Current version number |
| `<RS_ROOT>/Custom/active_theme.txt` | Active theme (while the switch is off) |
| `<RS_ROOT>/Custom/settings.json` | Switch and main theme settings |
| `<RS_ROOT>/plugins/customcss/defaults/` | Original themes (for "Restore original") |
| `<RS_ROOT>/plugins/customcss/lib/codemirror/` | Editor libraries (CodeMirror 5) |

The web server (`www-data`) needs write access to `Custom/`. The installer sets this up.
Since 1.04 the plugin also works when installed in `filestore/plugins`.

## License *(since 1.09)*

- The plugin is licensed under the **PolyForm Noncommercial License 1.0.0** (file
  [LICENSE](LICENSE)).
- It is **free** for private and noncommercial use, and for noncommercial organizations
  (education, public institutions, charities). You may change and share it for these purposes,
  keeping the license text.
- **Commercial use** (a company, services for clients, a paid product) requires the author's
  written permission: <https://github.com/s29829>. Details: `LICENSE-COMMERCIAL.md` in the
  repository.
- The CodeMirror 5 library in `lib/codemirror/` has its own MIT license (file `lib/codemirror/LICENSE`).
