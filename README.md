# Custom CSS – documentation

**English** | [Polski](README.pl.md)

Documentation for plugin version **1.34** (ResourceSpace 11).
*(since X.YY)* marks the plugin version in which a feature first appeared. Features without
a mark have been available since 1.00. Change history: [CHANGELOG.md](CHANGELOG.md).

The plugin manages the look of ResourceSpace. It stores themes, which are sets of CSS files
loaded on every page of the site (including the login page). You can edit them in the browser,
go back to earlier versions and give users a button that switches between light and dark mode.

**Where:** System > Plugins > Custom CSS > **Options**. Only administrators
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
- **×** on an extra file, or **Delete file** above the editor *(since 1.22)*, deletes the file.
  A saved file is deleted right away: the theme is stored as a new version without it (older
  versions still contain it), and other unsaved changes stay in the editor. A new file that was
  never saved is only removed from the editor. The three basic files cannot be deleted.
  In versions 1.13–1.21 **×** only removed the file from the editor; it was deleted on Save.
- **+ Add CSS file**, **Load original**, **Delete file**, **Undo** and **Redo** are outline
  buttons; Undo and Redo have arrow icons *(since 1.22)*.
- The line above the editor shows the file path and its role in the theme.
- **Load original** *(since 1.15)* – loads the original content of the current file of a theme
  shipped with the plugin (e.g. `custom-blue`) into the editor. It is not saved until you click
  **Save**; Undo brings your version back. The button is shown only for themes that have an
  original.

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
| **Exit** | Returns to the Plugins page (asks for confirmation if there are unsaved changes). |

Versions 1.00–1.12 had Save, Save & Exit and Save as new version (since 1.03), for one file.

### Themes

- **Theme** – the theme to edit (asks for confirmation if there are unsaved changes). "(active)"
  marks the theme active on the site.
- **Activate** / **Deactivate** – makes the theme active, or turns themes off on the site. The
  saved theme is activated; unsaved editor changes are not used.
- **Delete theme** – deletes the theme folder. Its version history is kept.
- **Deactivate** and **Delete theme** are outline buttons *(since 1.29)*.
- **Configurator** *(since 1.28)* – the settings of the selected theme in a form (see
  "Configurator" below). Since 1.29 it is the blue button above the editor, right after
  **+ Add CSS file** (1.28: in this box).
- **Duplicate** *(since 1.28)* – copies the saved files of the selected theme into a new theme. A
  popup asks for the name, filled in with `<theme> (copy)`; characters other than letters,
  numbers, `-` and `_` become `-` (e.g. `custom-blue-copy`). The copy starts with version v1.
- **Add a new theme > Create new theme** *(since 1.25; a blue button since 1.28, before "Create
  new…")* – opens the panel **Create a new theme** (see below): a blank theme or one made from a
  template with a form for its settings.
- Versions 1.00–1.27 had **Save as new theme** here (the editor content as a new theme) – replaced
  by **Duplicate** and **Create new theme** in 1.28.
- **Active on the site** – the active theme, the switch state and the default mode.
- **Download / upload (.zip)** *(since 1.22; needs the PHP zip extension)*:
  - **Download "<theme>"** – the saved files of the selected theme as `<theme>-<date>.zip`
    (folder `<theme>/` with the CSS files); **Download all themes** – one folder per theme.
  - **Upload theme** – imports a theme from a `.zip` with CSS files (in any folder; other files
    are ignored; at most 30 CSS files, 1 MB per file, 5 MB in total). The theme name is taken from
    the field, else from the folder in the archive, else from the archive name.
  - If the archive contains `main.css`, `light-mode.css` and `dark-mode.css` and the name is new,
    the theme is created at once (version v1). Otherwise a box asks which archive file to use for
    each of the three files – or to **create an empty file** – and offers **Replace the existing
    theme** when the name is taken (the old files stay in the version history). All other CSS files
    become extra files with their own names.
- Versions before 1.22 showed the theme folder on the server in this box and a sentence about
  "Save as new theme"; both were removed.

### Create a new theme *(since 1.25)*

**Create new theme** in the Themes box opens the panel. While it is open, the editor and the
boxes on the right are hidden *(since 1.28)*; at the bottom are **Create** and **Cancel**. Enter the
**name of the new theme** and choose how it starts:

- **Blank** – `main.css`, `light-mode.css` and `dark-mode.css` with only a header and empty PART A /
  PART B sections. For writing a theme from scratch.
- **Template** – a copy of an original theme (e.g. `custom-blue (original)`, always the version
  shipped with the plugin) or of an existing theme (its saved files), with a form for the
  **variables in PART A** of its files:
  - **main.css** at the top – the sections of the file, e.g. *1. Shapes and sizes* (buttons, large
    form buttons, fields, cards: corner shapes, heights, label weight, upper case), *2. Typography*
    (font from a list of fonts installed on most computers – web fonts from other sites are blocked
    by the security policy), *3. Login page*, *4. Home page banner*;
  - below in **two columns light-mode.css | dark-mode.css** the matching sections side by side
    (paired by their number), e.g. *Colour scheme and palette*, *Brand colours* (main colour, hover,
    pressed, tints), *ResourceSpace tokens* (backgrounds, forms, feedback), *Theme colours* (text,
    links, buttons, tables, login page).

  Each field shows the comment of the variable from the file as its label and the variable name
  below it. Colours have a **colour picker** next to the value; any CSS value can be typed as
  well (`var(--colour-brand-primary-default)`, `rgba(0, 0, 0, .25)`). Label weights and upper
  case are chosen from lists. The first sections are open, the others open with a click.
*(since 1.27)* **Switches** of the template – rules turned on or off with `@media all` /
`@media not all` and a comment with `SWITCH:` – are tick boxes in the section that mentions them,
e.g. **Hide the local login fields** (10.1) in *3. Login page*. 0/1 settings (`…-own`) are lists.

The form is built from the template files themselves (sections, groups and comments of PART A), so
it also works for your own themes when they follow the same layout (PART A with variables in
`:root`, section headers `N. TITLE`). Only the values of PART A change; the rules (PART B), the
comments and the extra files are copied as they are, and the theme name in the file headers is
replaced. Values cannot contain `;`, `{`, `}`, `<`, `>` or comments; `\` only before a hex code, e.g.
`"\2192"` *(since 1.26; before: no `\` at all)*.

**Create** saves the new theme as version v1 and opens it in the editor – check it with
**Preview** and activate it in the Themes box. Unsaved changes in the editor are lost (the page asks
first). **Cancel** shows the editor again.

### Configurator *(since 1.28)*

**Configurator** (above the editor, after **+ Add CSS file**) opens the same form for the **selected theme**, filled with its
values (from the saved files). The editor and the boxes on the right are hidden; at the bottom are
**Save theme** and **Cancel** (back to the editor).

- **Compare with** – the original the theme is compared with (default `custom-blue (original)`, as
  shipped with the plugin version), or *nothing* (only the variables of the theme).
- Variables of the original that the theme **does not have** are marked **missing in this theme**
  (orange; the sections that contain them are open; the number is at the top and in the section
  title). The value of the original is filled in; tick **add to the theme** for each one you want –
  changing its value ticks it, **tick all missing** ticks all. Unticked ones are not added. Added
  variables go into the `:root` block of the section with the same number in PART A (e.g. section 3
  for the login page), else into a new block at the end of PART A.
- **No rule of the theme uses it yet** – the theme is older than the variable (e.g. the SSO spacing,
  label and icon of 1.26 in a theme made with 1.25): adding the variable alone changes nothing. Then
  the form offers **replace the rules (PART B) of <file> with the rules of the original** – the
  values (PART A) and your section "Extra rules" / "Your own rules" are kept, other changes of the
  rules are replaced (the old files stay in the version history). Missing switches are solved the
  same way.
- *(since 1.31)* The same option is offered whenever the **rules of the theme differ from the
  original** – e.g. after an update of the plugin with fixed or new rules (switch states are not
  counted as a difference). Up to 1.30 it was offered only for missing settings.
- *(since 1.33)* A missing **palette colour** (e.g. `--lt-1` of the light mode) starts with the current
  colour of the theme's variables that use it in the original (e.g. `--colour-surface-view-background`).
  Added, those variables point to it (`var(--lt-1)`), so the theme looks the same and the palette
  colour then changes them – no "Load original" needed.
- Variables that only the theme has are in **Other variables of this theme**.
- **Save theme** stores all files of the theme as a new version (also when it is active – then the
  change is visible on the site at once) and opens it in the editor. Unsaved editor changes are not
  used (the page asks before opening the configurator).

**What the Configurator needs in the CSS files** *(comments in the files since 1.34)* – the files of
`custom-blue` say it in a **CONFIGURATOR – DO NOT CHANGE THESE PARTS** block at the top and in short
"Configurator: keep ..." notes next to the parts concerned. When editing a theme by hand, keep:

- the **PART A** and **PART B** headers (lines of `#`) – the Configurator changes only the variables
  above the PART B header,
- the **section headers** in PART A (a line of `=`, `N. TITLE`, a line of `=`) – the numbers pair the
  sections of `light-mode.css` and `dark-mode.css`,
- **one `:root` block per section** right after its header, without other braces inside it (missing
  variables are added before its closing brace),
- **one variable per line** (`--name: value;` and an optional comment = help text in the form; a
  comment alone on a line is the heading of a group),
- the **names of the variables**, the **switch comments** (e.g. `10.1 … SWITCH: …`) directly
  followed by `@media not all {` / `@media all {`, and the heading of the last section (**Extra rules**
  / **Your own rules**), which "replace the rules" keeps.

Values can be changed freely. Existing themes get the notes of PART B with "replace the rules"; the
block at the top of a file is part of PART A and stays as it was. Blank themes made with 1.34 or later
have the note in their PART B header. Up to 1.33 the files had no such comments – the rules were the same.

### Editor

Editor colours: Auto (follows the page), Light, Dark blue, Dracula, Monokai, Nord. The choice
is remembered in the browser. With **Auto**, the editor follows the light / dark switch at once
*(since 1.22)*; before, it changed only after reloading the page.

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
| **Enable the switch** | Every page shows a round button (bottom right) that each user can use to switch between light and dark mode – also when no theme is active *(since 1.17)*. |
| **Mode for users who have not chosen yet** | Default mode: follow the device setting, light or dark. While the switch is off, it applies to all users *(since 1.13)*. |

The fields "Main theme", "Light mode theme" and "Dark mode theme" of versions 1.00–1.12 were
removed *(since 1.13)*: the mode files are now part of the theme.

**How the button works** *(since 1.17)*:
- **Logged-in user:** the button saves the user's ResourceSpace appearance preference
  (`user_pref_appearance`: Light / Dark / Device default – the same setting as on the user
  preferences page). ResourceSpace's built-in dark mode switches at once, without reloading the
  page. The button shows the mode from this preference, so a change on the preferences page is
  shown too.
- **Active theme:** it also switches `light-mode.css` / `dark-mode.css`.
- **No active theme:** only ResourceSpace's dark mode switches.
- **Guests and the login page:** the choice is kept in a browser cookie (one year) and applies to
  the theme files.
- "Mode for users who have not chosen yet" applies to the theme and to users without a saved
  preference.

In versions 1.00–1.16 the button was shown only with an active theme (since 1.13: a theme with
both mode files) and switched only the theme files, keeping the choice in a cookie.

## Upgrading from older versions

Version 1.13 converts the old layout (one CSS file per theme) by itself, when any page of the site
or the options page is opened after the update:

- The themes selected in the switch, by default `main-blue` + `light-blue` + `dark-blue`, become
  the theme **`custom-blue`**, keeping your edits. If other themes were selected, the theme
  `migrated-switch` is created.
- Every other old theme `X.css` becomes the theme `X`: its content goes to `main.css`, and
  `light-mode.css` and `dark-mode.css` are empty.
- The active theme, the switch state and the default mode are kept.
- The old files and their version history are moved to a `legacy-<date>` folder in the plugin's data folder. Nothing is
  deleted.
- The options page shows what was converted.

**Missing rules after the conversion (fixed in 1.15).** If the server had a plugin version older
than 1.06 (without the `main-blue` theme), versions 1.13–1.14 created the `main.css` of
`custom-blue` with only a comment. The SSO button with its texts, hiding the local login and the
hero banner settings were then missing (e.g. `--cc-hero-height-desktop: 250px;` had no effect).

- Since 1.15 such an empty `main.css` is replaced with the original automatically and stored as
  a new version. A file with any other content is never changed.
- Old `light-mode.css` / `dark-mode.css` files (former complete themes) may still contain an old
  SSO button rule. Since 1.15 the rules in `main.css` are stronger, so the button is full width
  anyway. You can refresh the mode files with **Load original** above the editor, then **Save**.

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
6. **Keep your own variants in a copy of the theme** (**Duplicate** or **Create new theme**, e.g.
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

In both files it is easiest to change the palette in section 1 *Colour scheme and palette (edit here)*,
because the other values derive from it: `light-mode.css` – `--lt-0` … `--lt-5`, `--lt-text`,
`--lt-heading` *(since 1.33)*, e.g. `--lt-1` = **page background**; `dark-mode.css` – `--dk-0` …
`--dk-5`, `--dk-text*`. Single surfaces: section 3 (`--colour-surface-*`). Up to 1.32 the light
palette did not exist and the colours of the light mode were only in sections 3 and 4.

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

`main.css`, section 3. The SSO button is centred and as wide as the login fields. *(since 1.27)* All
settings of the login page are in this one section, in groups: **3.1 Local login fields** (the
switch in section 10.1, see below), **3.2 Spacing**, **3.3 SSO button**, **3.4 Label and icon**,
**3.5 Texts** – and the same in the form of **Create new theme** / **Configurator** (section *3. Login page*, with the
switch as a tick box). Only the colours of the title and texts stay in `light-mode.css` /
`dark-mode.css`, section 4.4, because they differ per mode.

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

**Spacing** *(since 1.26)* – before 1.26 the spaces were fixed (16px around the block, 12px between
the parts):

```css
--cc-login-sso-space-before: 16px; /* above the block, i.e. above the title "Single sign-on" */
--cc-login-sso-space-title:  12px; /* between the title / text and the button */
--cc-login-sso-space-note:   12px; /* between the button and the note */
--cc-login-sso-space-after:  16px; /* below the block */
```

**Label and icon of the button** *(since 1.26)* – by default the button shows the label and key
icon of ResourceSpace ("Use SSO to log in", in the user's language). Your own:

```css
--cc-login-sso-label-own: 1;                   /* 0 = ResourceSpace label, 1 = the label below */
--cc-login-sso-label:     "Log in with Microsoft";
--cc-login-sso-icon-own:  1;                   /* 0 = key icon of ResourceSpace, 1 = the icon below */
--cc-login-sso-icon:      "\2192";             /* a character: "\2192" arrow, "\1F511" key emoji, "" = none */
--cc-login-sso-icon-gap:  8px;                 /* space between the icon and the label */
```

In the form of **Create new theme** / **Configurator** a changed label or icon switches its
`…-own` setting to 1 by itself *(since 1.30; before: set it yourself, otherwise the label of
ResourceSpace stays)*.

**The label does not change?** Open the login page, press F12 > Console and run
`getComputedStyle(document.documentElement).getPropertyValue('--cc-login-sso-label-own')`:
empty = the active theme is older than 1.26 (no such variable – use the **Configurator** with
"add to the theme" and "replace the rules", or **Load original** on `main.css`); `0` = switched off
(set 1); `1` but the old label = the rules are missing (Configurator: "replace the rules (PART B)").

The own label is the same in every language. The icon is a text character (CSS code `\XXXX` or the
character itself, e.g. an emoji); its colour follows the label, emojis keep their own colours.
**Existing themes are not changed by an update:** the new variables and rules are in the original
`main.css` of `custom-blue` – take them over with **Load original** on the `main.css` tab (your
changes of that file are replaced; they stay in the version history) or create a new theme from
the template `custom-blue (original)` with **Create new theme**, or use the **Configurator** *(since
1.28)*: it lists the missing variables and can take the new rules.

### Hiding the local account login *(since 1.05)*

`main.css`, section 10.1. Change `@media not all {` (fields shown, default) to `@media all {`
(fields hidden).

- This hides the user name and password fields, "Keep me logged in", the "Log in" button and the
  "Request account" and "Forgotten password" links.
- The fields are hidden **only when an SSO button is on the page**. If SSO is switched off,
  nobody loses the ability to log in.
- Hiding is visual only. Local login still works, so this is not a security measure.
- *(since 1.31)* The fields are found by their ids, the same in ResourceSpace 10 and 11 (`#username`,
  `#password`, `#remember`, the `Submit` button, `#account_apply`, `#account_pw_reset`). Up to 1.30
  the rule only knew the class names of ResourceSpace 10 (`.Question` …) and **hid nothing in
  ResourceSpace 11**. Existing themes get the fixed rule with the **Configurator** ("replace the
  rules (PART B) of main.css") or **Load original**. The language and time zone lists stay visible.
- *(since 1.32)* The boxes around the hidden fields are hidden too (e.g. a framed or coloured box
  holding the user name, password and button), so no empty rectangle stays where the local login
  was. In 1.31 such a box could stay visible. Take the new rules with the **Configurator** as above.
- Switch it in **Create new theme** / **Configurator**: tick box **Hide the local login fields** in
  section *3. Login page*.

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

### Download and installation

The plugin is distributed as a `customcss-<version>.zip` archive on the releases page:
<https://github.com/s29829/custom-css/releases>. The archive contains the folder `customcss/` with the code,
license and documentation. Below, `<RS_ROOT>` is the ResourceSpace installation folder (e.g.
`/var/www/resourcespace`) and `www-data:www-data` is the user and group that run PHP – with Apache on RHEL, Rocky, Alma or
Fedora `apache:apache` (see "nginx or Apache" below).

**In the browser:** Plugin Manager > Upload a plugin > choose the `.zip` file > Upload (when
updating: **Overwrite**).

**From the command line (CLI)** – download, unpack and set the permissions:

```bash
VER=<version>                    # version number from the releases page, e.g. 1.00
cd /tmp
curl -fLO https://github.com/s29829/custom-css/releases/download/v$VER/customcss-$VER.zip
sudo rm -rf <RS_ROOT>/plugins/customcss        # only when updating (the plugin data is in the filestore)
sudo unzip -q customcss-$VER.zip -d <RS_ROOT>/plugins/
sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
sudo find <RS_ROOT>/plugins/customcss -type d -exec chmod 755 {} \;
sudo find <RS_ROOT>/plugins/customcss -type f -exec chmod 644 {} \;
```

**With git:** the repository <https://github.com/s29829/custom-css> contains the complete plugin folder. The
repository is called `custom-css` *(since 1.21; before: `customcss`)*, but the plugin folder must be
called `customcss` – hence the target folder in `git clone`:

```bash
sudo git clone https://github.com/s29829/custom-css <RS_ROOT>/plugins/customcss
sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
```

Update: `sudo git -C <RS_ROOT>/plugins/customcss pull`.

### nginx or Apache

The plugin works the same with both web servers. Only the user that runs PHP, the service names
and a few server settings differ – use the user and group from the table in the commands above:

| | nginx + PHP-FPM (Ubuntu / Debian) | Apache (Ubuntu / Debian) | Apache (RHEL, Rocky, Alma, Fedora) |
|---|---|---|---|
| User:group that runs PHP | `www-data:www-data` | `www-data:www-data` | `apache:apache` |
| Reload after an update | `sudo systemctl restart php8.3-fpm` | mod_php: `sudo systemctl reload apache2`; PHP-FPM: `sudo systemctl restart php8.3-fpm` | mod_php: `sudo systemctl reload httpd`; PHP-FPM: `sudo systemctl restart php-fpm` |

- **Who runs PHP:** with Apache and mod_php the Apache user; with PHP-FPM (nginx or Apache) the user
  of the PHP-FPM pool (`user =` / `group =` in `/etc/php/<version>/fpm/pool.d/www.conf`, on the RHEL
  family `/etc/php-fpm.d/www.conf`). Check: `ps -o user= -C php-fpm8.3,php-fpm,apache2,httpd | sort -u`.
- **Permissions:** folders `755`, files `644`, owner = that user and group (commands above). The plugin
  files only need to be readable; `<filestore>` must be writable for that user – exactly as
  ResourceSpace itself requires.
- **No directory listings (Apache):** Apache on Ubuntu / Debian allows listings under `/var/www`
  (`Options Indexes` in `apache2.conf`). The plugin keeps its settings in
  `<filestore>/<plugin>/data_<key>/`, and a listing would show the folder name. Switch listings off for
  ResourceSpace (VirtualHost or `apache2.conf` / `httpd.conf`) and reload Apache:

  ```apache
  <Directory <RS_ROOT>>
      Options -Indexes +FollowSymLinks
  </Directory>
  ```

  nginx shows no listings unless `autoindex on` is set.
- **SELinux (RHEL family):** after unpacking restore the file labels:
  `sudo restorecon -R <RS_ROOT>/plugins/customcss`. The filestore needs the writable label (usually set when
  ResourceSpace was installed): `sudo semanage fcontext -a -t httpd_sys_rw_content_t "<filestore>(/.*)?"`
  and `sudo restorecon -R <filestore>`.

Finally: System > Plugins, activate "Custom CSS" and click **Options**. The plugin creates its
data folder `<filestore>/customcss` itself – it only needs write access to the filestore, which
ResourceSpace requires anyway.

- All installation methods replace only the plugin files *(since 1.13)*. The themes
  (since 1.16 in `<filestore>/customcss`, also your edits of `custom-blue`), the history and the
  settings are kept.
- A new version of the original goes to `defaults/`; restore it with **Restore original**.
- In versions 1.00–1.12 an update overwrote the original themes in `<RS_ROOT>/Custom/themes` and
  stored their previous content in the history.

| Path | Content |
|---|---|
| `<filestore>/customcss/themes/<theme>/` | Theme files: `main.css`, `light-mode.css`, `dark-mode.css`, extra `*.css` (public – loaded by browsers) |
| `<filestore>/customcss/data_<key>/settings.json` | Active theme, switch, default mode |
| `<filestore>/customcss/data_<key>/versions/<theme>/v<N>_<date>_<time>/` | Theme versions (all files) |
| `<filestore>/customcss/data_<key>/legacy-<date>/` | Files of older layouts (after the conversion) |
| `<RS_ROOT>/plugins/customcss/defaults/<theme>/` | Original themes (for "Restore original") |
| `<RS_ROOT>/plugins/customcss/lib/codemirror/` | Editor libraries (CodeMirror 5) |

`<filestore>` is ResourceSpace's `$storagedir` (default `<RS_ROOT>/filestore`). The key in
`data_<key>` derives from `scramble_key`, so the settings and history cannot be read by guessing
the address. The themes are served to browsers through `$storageurl`, like the logo from System >
Appearance.

**The plugin creates its folders itself** on first use *(since 1.16)*: `filestore/customcss/themes`
and the data folder, then the original themes. It only needs write access to the filestore, which
ResourceSpace requires anyway.

**Data move *(since 1.16)*.** In versions 1.00–1.15 the data was stored in `<RS_ROOT>/Custom/`.
Version 1.16 copies it the first time any page of the site or the options page is opened:
- `Custom/themes` → `filestore/customcss/themes`,
- versions, settings and `legacy-*` files → the data folder.

The old folder is kept as a backup, `Custom-moved-<date>`, and can be deleted. If it cannot be
renamed, it keeps its old name. The plugin also works when installed in `filestore/plugins`
*(since 1.04)*.

## Reset plugin *(since 1.16)*

The **Reset plugin** box on the options page (after typing `RESET`) deletes **everything the
plugin stored** in `<filestore>/customcss`: all themes (also your own), the version history and
the settings. The original themes are created again, but no theme is active until you activate
one. This cannot be undone. The plugin files themselves are not changed.

## License *(since 1.09)*

- The plugin is licensed under the **PolyForm Noncommercial License 1.0.0** (file
  [LICENSE](LICENSE)).
- It is **free** for private and noncommercial use, and for noncommercial organizations
  (education, public institutions, charities). You may change and share it for these purposes,
  keeping the license text.
- **Commercial use** (a company, services for clients, a paid product) requires the author's
  written permission: <https://github.com/s29829>. Details: [LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md).
- The CodeMirror 5 library in `lib/codemirror/` has its own MIT license (file `lib/codemirror/LICENSE`).
