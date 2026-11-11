# Custom CSS – dokumentacja

[English](README.md) | **Polski**

Dokumentacja dla wersji wtyczki **1.12** (ResourceSpace 11).
Znacznik *(od X.YY)* oznacza wersję wtyczki, od której funkcja jest dostępna. Funkcje bez
znacznika są dostępne od wersji 1.00. Historia zmian: [CHANGELOG.md](CHANGELOG.md).

Wtyczka zarządza wyglądem ResourceSpace. Przechowuje motywy, czyli pliki CSS ładowane na
każdej stronie serwisu (także na stronie logowania). Pozwala je edytować w przeglądarce,
przywracać starsze wersje i udostępnić użytkownikom przycisk przełączania trybu jasny / ciemny.

**Gdzie:** System > Plugin Manager > Custom CSS > **Options**. Strona jest dostępna tylko dla
administratorów (uprawnienie `a`).

## Spis treści

1. [Jak to działa](#jak-to-działa)
2. [Motywy dostarczone z wtyczką](#motywy-dostarczone-z-wtyczką)
3. [Strona opcji](#strona-opcji)
4. [Zasady edycji motywów](#zasady-edycji-motywów)
5. [Najczęstsze zmiany – gdzie szukać](#najczęstsze-zmiany--gdzie-szukać)
6. [Instalacja i pliki na serwerze](#instalacja-i-pliki-na-serwerze)

---

## Jak to działa

- Każdy motyw to jeden plik `Custom/themes/<nazwa>.css`.
- Wtyczka dodaje motywy na końcu `<head>`, za stylami ResourceSpace (`global.css`, `light.css`),
  dzięki czemu mogą je nadpisywać.
- ResourceSpace 11 koloruje interfejs zmiennymi CSS (tzw. design tokens, np.
  `--colour-brand-primary-default`). Motywy zmieniają przede wszystkim te zmienne, więc zmiana
  jednej wartości przemalowuje wszystkie elementy, które z niej korzystają.
- Kolejność ładowania (motyw ładowany później wygrywa przy konflikcie):
  1. **motyw główny („Main theme”)** – część wspólna dla obu trybów *(od 1.06)*,
  2. **motyw jasny lub ciemny** (gdy przełącznik jest włączony) albo **jeden aktywny motyw**
     (gdy przełącznik jest wyłączony).
- Adres pliku zawiera datę modyfikacji (`?v=…`), więc po zapisie przeglądarki od razu pobierają
  nową wersję.

## Motywy dostarczone z wtyczką

| Motyw | Zawartość |
|---|---|
| `main-blue` *(od 1.06)* | Część **wspólna** dla trybu jasnego i ciemnego: kształty i rozmiary (przyciski, pola, karty), czcionka, strona logowania (przycisk SSO, teksty, ukrywanie logowania lokalnego), baner hero na stronie głównej oraz reguły, które pobierają kolory ze zmiennych. |
| `light-blue` | **Kolory trybu jasnego**: paleta, kolory marki, tokeny ResourceSpace, kolory tekstu, przycisków i tabel. |
| `dark-blue` | **Kolory trybu ciemnego** (te same sekcje i nazwy zmiennych co `light-blue`) oraz reguły dla elementów, które ResourceSpace koloruje na sztywno jasnymi barwami (panele, okna dialogowe, komunikaty, statusy zasobów). |

- Od wersji 1.06 `light-blue` i `dark-blue` zawierają tylko kolory. Należy ich używać razem
  z `main-blue` jako motywem głównym.
- W wersjach 1.00–1.05 `light-blue` i `dark-blue` były pełnymi, samodzielnymi motywami.
- Od wersji 1.05 oba pliki mają ten sam układ sekcji i te same nazwy zmiennych `--cc-*`.

Oryginały motywów są w katalogu wtyczki (`plugins/customcss/defaults/`). Można je w każdej
chwili przywrócić (zob. [Version history](#version-history)).

## Strona opcji

Po lewej jest edytor, po prawej rozwijane panele ustawień *(układ dwukolumnowy z rozwijanymi
panelami: od 1.01)*.

### Edytor

Edytor CodeMirror z kolorowaniem składni, numerami linii i zwijaniem bloków `{ }`. Ma też
podpowiedzi właściwości (pojawiają się w trakcie pisania albo po **Ctrl+Space**).

| Skrót | Działanie |
|---|---|
| Ctrl+S / Cmd+S | Zapis (Save) |
| Ctrl+Space | Podpowiedzi |
| Ctrl+/ | Zakomentuj / odkomentuj |
| Tab | Wcięcie (4 spacje) |

**Syntax check** pod edytorem na bieżąco sprawdza CSS. Wykrywa niezamknięte nawiasy,
komentarze i cudzysłowy, brakujące `;` oraz nieznane właściwości i wartości (ostrzeżenia).
Jeśli przy zapisie są błędy, strona prosi o potwierdzenie.

Przyciski pod edytorem:

| Przycisk | Działanie |
|---|---|
| **Save** | Zapisuje motyw. Poprzednia treść trafia do historii pod bieżącym numerem wersji. |
| **Save & Exit** | Zapisuje i wraca do Plugin Managera. |
| **Save as new version** *(od 1.03)* | Zapisuje i zaczyna kolejny numer wersji (np. v3 → v4). |
| **Preview** | Pokazuje pod edytorem wybraną stronę serwisu z bieżącą treścią edytora, bez zapisywania. Adres strony można zmienić (domyślnie `pages/home.php`). Podgląd odświeża się w trakcie pisania. |
| **Exit** | Wraca do Plugin Managera (pyta o potwierdzenie, jeśli są niezapisane zmiany). |

W podglądzie edytowany motyw zastępuje motyw jasny / ciemny (lub aktywny), a motyw główny
zostaje włączony. Przy edycji samego motywu głównego zostaje włączony motyw jasny / ciemny
*(od 1.06)*.

### Themes

- **Theme** – wybór motywu do edycji. Przy niezapisanych zmianach strona pyta o potwierdzenie.
  Dopisek „(active)” oznacza motyw aktywny przy wyłączonym przełączniku.
- **Save & activate** / **Deactivate** – ustawia lub wyłącza pojedynczy aktywny motyw (gdy
  przełącznik trybu jest wyłączony).
- **Delete theme** – usuwa plik motywu. Jego historia wersji zostaje.
- **Add a new theme** – zapisuje bieżącą treść edytora pod nową nazwą (małe litery, cyfry,
  `-`, `_`, maks. 40 znaków).
- **Active on the site** – co jest teraz ładowane w serwisie, np.
  `main-blue + light/dark switch (light-blue / dark-blue)`.

### Editor

Kolory edytora: Auto (dopasowane do strony), Light, Dark blue, Dracula, Monokai, Nord. Wybór
jest zapamiętywany w przeglądarce.

### Version history

- Każdy **Save** zapisuje poprzednią treść jako plik `v<N>_<data>_<godzina>.css`.
  Przechowywanych jest 30 ostatnich wpisów na motyw.
- Lista wersji pokazuje numer i datę, od najstarszej *(od 1.02)*.
- **Load into editor** – wczytuje wybraną wersję do edytora. Staje się bieżąca dopiero po Save.
- **Delete this version** *(od 1.03)* – usuwa wszystkie pliki o danym numerze wersji.
- **Delete all history** *(od 1.03)* – usuwa całą historię motywu poza najnowszym wpisem.
- **Original themes > Restore original** – zastępuje motyw oryginałem z wtyczki albo odtwarza
  go, jeśli został usunięty. Obecna treść trafia do historii. Dostępne oryginały: `light-blue`,
  `dark-blue` oraz `main-blue` *(od 1.06)*.

### Light / dark mode switch

Po włączeniu na każdej stronie pojawia się okrągły przycisk (prawy dolny róg). Każdy
użytkownik może nim przełączać tryb jasny i ciemny. Wybór jest zapamiętywany w ciasteczku
przeglądarki na rok.

| Pole | Znaczenie |
|---|---|
| **Enable the switch** | Włącza przycisk. Przełącznik zastępuje wtedy pojedynczy „aktywny motyw”. |
| **Main theme (shared part)** *(od 1.06)* | Motyw wspólny, ładowany **przed** motywem jasnym / ciemnym. Ładuje się również przy wyłączonym przełączniku, przed pojedynczym aktywnym motywem. Domyślnie `main-blue`; „- none -” wyłącza go. Musi być innym plikiem niż motyw jasny i ciemny. |
| **Light mode theme** | Motyw trybu jasnego (domyślnie `light-blue`). |
| **Dark mode theme** | Motyw trybu ciemnego (domyślnie `dark-blue`). |
| **Mode for users who have not chosen yet** | Tryb domyślny: zgodny z ustawieniem urządzenia, jasny albo ciemny. |

Przycisk przełącza tylko motyw jasny / ciemny. Motyw główny zostaje załadowany.

## Zasady edycji motywów

1. **Najpierw Preview, potem Save.** Podgląd niczego nie zapisuje, więc można spokojnie
   próbować.
2. **Zmieniaj wartości zmiennych, a nie reguły.** Każdy motyw ma dwie części *(od 1.05)*:
   - **Część A – zmienne** (bloki `:root { … }`). Tu zmienia się kolory, rozmiary i teksty.
   - **Część B – reguły**, które używają tych zmiennych. Zwykle nie trzeba ich ruszać.
3. **Właściwe miejsce dla zmiany** *(od 1.06)*:
   - zmiana dotyczy obu trybów (kształt, rozmiar, czcionka, logowanie, baner) → `main-blue`,
   - zmiana dotyczy tylko kolorów jednego trybu → `light-blue` lub `dark-blue`.
4. **Ta sama zmiana koloru w obu trybach.** `light-blue` i `dark-blue` mają te same sekcje
   i nazwy zmiennych. Jeśli zmieniasz np. `--cc-table-head-bg`, zrób to w obu plikach.
5. **Własne reguły dopisuj na końcu**, w sekcji „Extra rules” / „Your own rules” odpowiedniego
   pliku. Elementy, które w trybie ciemnym zostają jasne, sprawdź w przeglądarce (F12),
   skopiuj ich selektor i dodaj regułę w `dark-blue`.
6. **Zmiany oryginałów mogą zostać nadpisane.** Instalator wtyczki zastępuje motywy oryginalne
   wersjami z wtyczki (poprzednia treść trafia do historii). Większe własne zmiany lepiej trzymać
   w kopii (**Add a new theme**, np. `dark-blue-custom`) i wybrać ją w ustawieniach
   przełącznika.
7. **Czcionki z innych serwerów nie zadziałają.** Polityka bezpieczeństwa (CSP) blokuje fonty
   z zewnętrznych adresów. Użyj czcionek systemowych albo umieść pliki fontów na serwerze.
8. **Kolory podawaj z `#`**, np. `#2563EB`. Sprawdzanie składni podpowie, jeśli `#` brakuje.

## Najczęstsze zmiany – gdzie szukać

Numery sekcji dotyczą motywów z wersji 1.06 i nowszych.

### Kolor główny (przyciski, aktywne elementy)

`light-blue` i `dark-blue`, sekcja 2:

```css
--colour-brand-primary-default: #2563EB; /* kolor główny */
--colour-brand-primary-hover:   #3B82F6; /* po najechaniu */
--colour-brand-primary-dark:    #1D4ED8; /* po wciśnięciu */
```

### Tło strony, nagłówka, kart

`light-blue` sekcja 3 (`--colour-surface-*`). W `dark-blue` najprościej zmienić paletę
w sekcji 1 (`--dk-0` … `--dk-5`, `--dk-text*`), bo pozostałe wartości z niej wynikają.

### Kształt przycisków i pól

`main-blue`, sekcja 1:

```css
--cc-cta-radius:   5px;  /* 0 = kwadrat, 999px = pigułka */
--cc-cta-weight:   600;  /* grubość napisu */
--cc-field-radius: 5px;  /* pola formularzy */
--cc-card-radius:  8px;  /* karty i panele */
```

### Czcionka

`main-blue`, sekcja 2: `--cc-font`.

### Strona logowania – przycisk SSO i teksty *(od 1.05)*

`main-blue`, sekcja 3. Przycisk SSO jest wyśrodkowany i ma szerokość pól logowania.

```css
--cc-login-sso-title: "Single sign-on";   /* tytuł nad przyciskiem */
--cc-login-sso-text:  "Log in with ...";  /* tekst pod tytułem */
--cc-login-sso-note:  "Problems ...";     /* krótki opis pod przyciskiem */
```

- `""` daje pusty tekst.
- `none` w `--cc-login-sso-title` usuwa tytuł razem z tekstem.
- `none` w `--cc-login-sso-note` usuwa opis.
- Nowa linia w tekście: `\A`, np. `"Pierwsza linia\ADruga linia"`.
- Kolory tytułu i tekstów są w `light-blue` / `dark-blue`, sekcja 4.4.

### Ukrycie logowania kontami lokalnymi *(od 1.05)*

`main-blue`, sekcja 10.1. Zmień:

```css
@media not all {   /* pola widoczne (domyślnie) */
```

na

```css
@media all {       /* pola ukryte */
```

Ukrywane są pola użytkownika i hasła, „Keep me logged in”, przycisk „Log in” oraz linki
„Request account” i „Forgotten password”.

- Pola znikają **tylko wtedy, gdy na stronie jest przycisk SSO**. Jeśli SSO zostanie wyłączone,
  nikt nie straci możliwości zalogowania.
- Ukrycie jest wyłącznie wizualne. Logowanie lokalne dalej działa, więc nie jest to
  zabezpieczenie. Administrator może się zalogować lokalnie po przywróceniu `@media not all`.

### Wysokość baneru hero (slider na stronie głównej) *(od 1.05)*

`main-blue`, sekcja 4. Obrazy slidera ustawia się w ResourceSpace (System > Slideshow).
Na stronie głównej wtyczka zamienia pełnoekranowe tło w baner u góry strony.

```css
--cc-hero-height-desktop: 420px;      /* np. 300px, 50vh; 100vh = cały ekran */
--cc-hero-height-mobile:  240px;      /* ekrany węższe niż 820px */
--cc-hero-position:       center top; /* który fragment obrazu jest widoczny */
--cc-hero-content-offset: 0px;        /* odstęp treści od góry, np. by zaczynała się pod banerem */
```

Granica „mobile” (819px) jest wpisana w regule `@media` w sekcji 11, bo zapytania `@media`
nie mogą korzystać ze zmiennych.

## Instalacja i pliki na serwerze

### Z archiwum zip *(od 1.10)*

Wtyczka jest dystrybuowana osobno, jako archiwum `customcss-<wersja>.zip` (do pobrania z
<https://github.com/s29829/customcss/releases>, od 1.11). Archiwum zawiera katalog `customcss/` z kodem, licencją i dokumentacją.

1. Pobierz `customcss-<wersja>.zip` i opcjonalnie sprawdź sumę kontrolną:
   `sha256sum -c customcss-<wersja>.zip.sha256`.
   Serwer WWW musi mieć prawo zapisu do katalogu `<RS_ROOT>/Custom`:
   `sudo install -d -o www-data -g www-data <RS_ROOT>/Custom`.
2. Zainstaluj jednym ze sposobów:
   - przez **Plugin Admin**: Upload a plugin > wybierz plik > Upload (przy aktualizacji:
     **Overwrite**),
   - skryptem dołączonym do wydania *(od 1.12)*: `bash rs-plugin-zip-install.sh customcss-<wersja>.zip`.
     Sprawdza sumę kontrolną i składnię PHP, podmienia cały katalog wtyczki, ustawia uprawnienia
     i restartuje PHP-FPM. Inna ścieżka ResourceSpace: `RS_ROOT=/ścieżka bash …`,
   - ręcznie na serwerze (przy aktualizacji najpierw usuń stary katalog `customcss`; dane wtyczki
     są poza nim):
     ```bash
     sudo unzip customcss-<wersja>.zip -d <RS_ROOT>/plugins/
     sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
     ```
3. System > Plugin Manager, aktywuj „Custom CSS” i kliknij **Options**.

Przy pierwszym otwarciu strony opcji wtyczka tworzy `Custom/themes` i kopiuje do niego brakujące
motywy oryginalne *(od 1.10)*. Motyw, który ma historię wersji (czyli został usunięty celowo),
nie jest odtwarzany. Jeśli katalogu nie da się utworzyć, strona pokazuje, jak nadać uprawnienia.

### Przez git *(od 1.11)*

Repozytorium <https://github.com/s29829/customcss> zawiera pełny katalog wtyczki:

```bash
sudo git clone https://github.com/s29829/customcss <RS_ROOT>/plugins/customcss
sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
```

Aktualizacja: `sudo git -C <RS_ROOT>/plugins/customcss pull`.

### Instalatorem z konsoli

`bash customcss-install.sh` (instalator z wbudowanymi plikami) lub `bash customcss-github-install.sh`
(pobiera wtyczkę z <https://github.com/s29829/customcss>, bez tokenu).

- Instalatory nadpisują pliki wtyczki i motywy oryginalne. Przedtem zapisują ich obecną treść
  w historii wersji.
- Własne motywy, historia i ustawienia zostają.
- Aktualizacja z archiwum zip nie zmienia istniejących motywów w `Custom/themes`. Nowe wersje
  oryginałów trafiają do `defaults/` (przywraca się je przyciskiem **Restore original**), a motywy
  oryginalne, których jeszcze nie ma, są tworzone automatycznie.

| Ścieżka | Zawartość |
|---|---|
| `<RS_ROOT>/Custom/themes/<nazwa>.css` | Motywy |
| `<RS_ROOT>/Custom/versions/<nazwa>/v<N>_<data>_<godz>.css` | Historia wersji motywu |
| `<RS_ROOT>/Custom/versions/<nazwa>/current_version.txt` | Bieżący numer wersji |
| `<RS_ROOT>/Custom/active_theme.txt` | Aktywny motyw (przy wyłączonym przełączniku) |
| `<RS_ROOT>/Custom/settings.json` | Ustawienia przełącznika i motywu głównego |
| `<RS_ROOT>/plugins/customcss/defaults/` | Oryginalne motywy (do „Restore original”) |
| `<RS_ROOT>/plugins/customcss/lib/codemirror/` | Biblioteki edytora (CodeMirror 5) |

Katalog `Custom/` musi być zapisywalny dla serwera WWW (`www-data`). Instalator ustawia to
automatycznie. Od wersji 1.04 wtyczka działa także zainstalowana w `filestore/plugins`.

## Licencja *(od 1.09)*

- Wtyczka jest udostępniona na licencji **PolyForm Noncommercial License 1.0.0** (plik
  [LICENSE](LICENSE)).
- **Bezpłatnie** można jej używać prywatnie i w celach niekomercyjnych, a także w organizacjach
  niekomercyjnych (edukacja, instytucje publiczne, organizacje charytatywne). Można ją zmieniać
  i udostępniać w tych celach, z zachowaniem tekstu licencji.
- **Użycie komercyjne** (firma, usługi dla klientów, płatny produkt) wymaga pisemnej zgody
  autora: <https://github.com/s29829>. Szczegóły: `LICENSE-COMMERCIAL.md` w repozytorium.
- Biblioteka CodeMirror 5 w `lib/codemirror/` ma własną licencję MIT (plik `lib/codemirror/LICENSE`).
