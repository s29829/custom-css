# Custom CSS – dokumentacja

[English](README.md) | **Polski**

Dokumentacja dla wersji wtyczki **1.29** (ResourceSpace 11).
Znacznik *(od X.YY)* oznacza wersję wtyczki, od której funkcja jest dostępna. Funkcje bez
znacznika są dostępne od wersji 1.00. Historia zmian (po angielsku): [CHANGELOG.md](CHANGELOG.md).

Wtyczka zarządza wyglądem ResourceSpace. Przechowuje motywy, czyli zestawy plików CSS ładowanych
na każdej stronie serwisu (także na stronie logowania). Pozwala je edytować w przeglądarce,
wracać do wcześniejszych wersji i udostępnić użytkownikom przycisk przełączania trybu jasny /
ciemny.

**Gdzie:** System > Plugins > Custom CSS > **Options**. Strona jest dostępna tylko dla
administratorów (uprawnienie `a`).

## Spis treści

1. [Jak to działa](#jak-to-działa)
2. [Motyw custom-blue](#motyw-custom-blue)
3. [Strona opcji](#strona-opcji)
4. [Przejście ze starszych wersji](#przejście-ze-starszych-wersji)
5. [Zasady edycji motywów](#zasady-edycji-motywów)
6. [Najczęstsze zmiany – gdzie szukać](#najczęstsze-zmiany--gdzie-szukać)
7. [Instalacja i pliki na serwerze](#instalacja-i-pliki-na-serwerze)
8. [Licencja](#licencja-od-109)

---

## Jak to działa

**Motyw to katalog z co najmniej trzema plikami CSS** *(od 1.13)*:

| Plik | Kiedy jest ładowany | Co zawiera |
|---|---|---|
| `main.css` | zawsze, jako pierwszy | wszystko, co jest takie samo w trybie jasnym i ciemnym: kształty, rozmiary, czcionki, strona logowania, baner |
| `<dodatkowy>.css` | zawsze, po `main.css` (alfabetycznie) | opcjonalne pliki dodane w edytorze, np. `print.css`, `home.css` |
| `light-mode.css` | w trybie jasnym, jako ostatni | kolory trybu jasnego |
| `dark-mode.css` | w trybie ciemnym, jako ostatni | kolory trybu ciemnego |

- W serwisie działa **jeden aktywny motyw**. Plik ładowany później wygrywa przy konflikcie.
- **Który tryb:** zależy od ustawień przełącznika (zob.
  [Light / dark mode switch](#light--dark-mode-switch)):
  - przełącznik włączony: każdy użytkownik wybiera tryb przyciskiem,
  - przełącznik wyłączony: wszyscy dostają tryb ustawiony w „Mode for users who have not chosen
    yet”.

  Przy trybie „Follow the device setting” przeglądarka sama wybiera `light-mode.css` albo
  `dark-mode.css` według ustawienia systemu.
- ResourceSpace 11 koloruje interfejs zmiennymi CSS (tzw. design tokens, np.
  `--colour-brand-primary-default`). Motywy zmieniają przede wszystkim te zmienne, więc zmiana
  jednej wartości przemalowuje wszystkie elementy, które z niej korzystają.
- Wtyczka dodaje pliki motywu na końcu `<head>`, za stylami ResourceSpace, więc mogą je nadpisywać.
  Adresy plików zawierają datę modyfikacji (`?v=…`), dzięki czemu po zapisie przeglądarki od
  razu pobierają nową wersję.

W wersjach 1.00–1.12 motyw był jednym plikiem CSS. Przełącznik trybu wybierał osobno motyw
jasny, ciemny i (od 1.06) główny. Zob. [Przejście ze starszych wersji](#przejście-ze-starszych-wersji).

## Motyw custom-blue

Motyw oryginalny dostarczany z wtyczką *(od 1.13; wcześniej jako trzy motywy `main-blue`,
`light-blue`, `dark-blue`)*:

| Plik | Zawartość |
|---|---|
| `main.css` | kształty i rozmiary (przyciski, pola, karty), czcionka, strona logowania (przycisk SSO, teksty, ukrywanie logowania lokalnego) *(od 1.05)*, baner hero *(od 1.05)* oraz reguły, które pobierają kolory ze zmiennych |
| `light-mode.css` | paleta trybu jasnego, kolory marki, tokeny ResourceSpace, kolory tekstu, przycisków i tabel |
| `dark-mode.css` | paleta trybu ciemnego (te same sekcje i nazwy zmiennych co `light-mode.css`) oraz reguły dla elementów, które ResourceSpace koloruje na sztywno jasnymi barwami |

Oryginał jest w katalogu wtyczki (`plugins/customcss/defaults/custom-blue/`). Można go w każdej
chwili przywrócić: **Version history > Original themes > Restore original**.

## Strona opcji

Po lewej jest edytor, po prawej rozwijane panele ustawień.

### Zakładki plików *(od 1.13)*

Nad edytorem są zakładki z plikami motywu: `main.css`, dodatkowe pliki, `light-mode.css` i
`dark-mode.css`.

- Kliknięcie zakładki przełącza edytor na ten plik. Kropka „•” przy nazwie oznacza niezapisane
  zmiany.
- **+ Add CSS file** dodaje kolejny plik: podajesz nazwę, a „.css” jest dopisywane. Dodatkowe
  pliki ładują się w obu trybach, po `main.css`.
- **×** przy dodatkowym pliku albo **Delete file** nad edytorem *(od 1.22)* usuwa plik.
  Zapisany plik znika od razu: motyw zapisuje się jako nowa wersja bez niego (starsze wersje
  nadal go zawierają), a inne niezapisane zmiany zostają w edytorze. Nowy, jeszcze niezapisany
  plik jest tylko usuwany z edytora. Trzech podstawowych plików nie da się usunąć.
  W wersjach 1.13–1.21 **×** usuwał plik tylko z edytora, a z serwera znikał przy zapisie.
- **+ Add CSS file**, **Load original**, **Delete file**, **Undo** i **Redo** to przyciski
  w wersji outline (obramowanie bez wypełnienia); Undo i Redo mają ikony strzałek *(od 1.22)*.
- Nad edytorem widać ścieżkę pliku i jego rolę w motywie.
- **Load original** *(od 1.15)* – wczytuje do edytora oryginalną treść bieżącego pliku motywu
  dostarczonego z wtyczką (np. `custom-blue`). Zmiana nie jest zapisywana do kliknięcia
  **Save**, a Undo przywraca Twoją wersję. Przycisk widać tylko w motywach, które mają oryginał.

### Edytor

Edytor CodeMirror z kolorowaniem składni, numerami linii, zwijaniem bloków `{ }` i
podpowiedziami właściwości (pojawiają się w trakcie pisania albo po **Ctrl+Space**).

| Skrót (Mac: Cmd zamiast Ctrl) | Działanie |
|---|---|
| Ctrl+S | Zapis wszystkich plików (nowa wersja) |
| Ctrl+Z | Cofnij (undo) |
| Ctrl+Y / Ctrl+Shift+Z | Ponów (redo) |
| Ctrl+Space | Podpowiedzi |
| Ctrl+/ | Zakomentuj / odkomentuj |
| Tab | Wcięcie (4 spacje) |

- **Undo / Redo** działają też przyciskami nad edytorem *(od 1.13)*. Każda zakładka ma własną
  historię cofania, która znika dopiero po przeładowaniu strony (np. po zapisie).
- **Syntax check** pod edytorem sprawdza na bieżąco plik z otwartej zakładki: niezamknięte
  nawiasy, komentarze i cudzysłowy, brakujące `;`, nieznane właściwości i wartości. Przy zapisie
  sprawdzane są wszystkie pliki. Jeśli któryś ma błędy, strona prosi o potwierdzenie.

Przyciski pod edytorem:

| Przycisk | Działanie |
|---|---|
| **Save all files (new version)** *(od 1.13)* | Zapisuje **wszystkie** pliki motywu naraz, także te niezmienione, i zapisuje je razem jako kolejną wersję z datą i godziną (np. v4). |
| **Discard changes** *(od 1.13)* | Przywraca wszystkie pliki do ostatnio zapisanej wersji: cofa zmiany, usuwa dodane pliki i przywraca usunięte. Po odrzuceniu zmian Undo nadal działa w każdym pliku. |
| **Preview** | Pokazuje pod edytorem wybraną stronę serwisu z treścią edytora (`main.css` + dodatkowe pliki + plik trybu wybranego obok adresu: Light mode / Dark mode), bez zapisywania. Odświeża się w trakcie pisania. |
| **Exit** | Wraca do strony Plugins (pyta o potwierdzenie, jeśli są niezapisane zmiany). |

W wersjach 1.00–1.12 były przyciski Save, Save & Exit i Save as new version (od 1.03), które
dotyczyły jednego pliku.

### Themes

- **Theme** – wybór motywu do edycji (przy niezapisanych zmianach strona pyta o potwierdzenie).
  Dopisek „(active)” oznacza motyw aktywny w serwisie.
- **Activate** / **Deactivate** – ustawia motyw jako aktywny albo wyłącza motywy w serwisie.
  Aktywowany zostaje zapisany stan motywu, a niezapisane zmiany w edytorze nie.
- **Delete theme** – usuwa katalog motywu. Jego historia wersji zostaje.
- **Deactivate** i **Delete theme** to przyciski w wersji outline *(od 1.29)*.
- **Configurator** *(od 1.28)* – ustawienia wybranego motywu w formularzu (zob. „Konfigurator”
  niżej). Od 1.29 to niebieski przycisk nad edytorem, zaraz za **+ Add CSS file** (w 1.28: w tej
  ramce).
- **Duplicate** *(od 1.28)* – kopiuje zapisane pliki wybranego motywu do nowego motywu. Okienko
  pyta o nazwę, wstępnie wpisaną jako `<motyw> (copy)`; znaki inne niż litery, cyfry, `-` i `_`
  zamieniają się w `-` (np. `custom-blue-copy`). Kopia zaczyna od wersji v1.
- **Add a new theme > Create new theme** *(od 1.25; niebieski przycisk od 1.28, wcześniej „Create
  new…”)* – otwiera panel **Create a new theme** (zob. niżej): pusty motyw albo motyw z szablonu
  z formularzem jego ustawień.
- Wersje 1.00–1.27 miały tu **Save as new theme** (zawartość edytora jako nowy motyw) – w 1.28
  zastąpione przez **Duplicate** i **Create new theme**.
- **Active on the site** – aktywny motyw, stan przełącznika i tryb domyślny.
- **Download / upload (.zip)** *(od 1.22; wymaga rozszerzenia PHP zip)*:
  - **Download "<motyw>"** – zapisane pliki wybranego motywu jako `<motyw>-<data>.zip`
    (katalog `<motyw>/` z plikami CSS); **Download all themes** – wszystkie motywy, każdy
    w osobnym katalogu.
  - **Upload theme** – import motywu z `.zip` z plikami CSS (w dowolnym katalogu; inne pliki są
    pomijane; maks. 30 plików CSS, 1 MB na plik, 5 MB łącznie). Nazwa motywu pochodzi z pola,
    a gdy jest puste – z katalogu w archiwum albo z nazwy archiwum.
  - Gdy archiwum zawiera `main.css`, `light-mode.css` i `dark-mode.css`, a nazwa jest nowa, motyw
    powstaje od razu (wersja v1). W przeciwnym razie okno pyta, którego pliku z archiwum użyć dla
    każdego z trzech plików – albo czy **utworzyć pusty plik** – i proponuje **Replace the existing
    theme**, gdy nazwa jest zajęta (stare pliki zostają w historii wersji). Pozostałe pliki CSS
    stają się dodatkowymi plikami pod własnymi nazwami.
- Wersje przed 1.22 pokazywały w tej ramce katalog motywu na serwerze i zdanie o „Save as new
  theme”; oba elementy usunięto.

### Tworzenie nowego motywu *(od 1.25)*

**Create new theme** w ramce Themes otwiera panel. Gdy jest otwarty, edytor i ramki po prawej są
ukryte *(od 1.28)*; na dole są przyciski **Create** i **Cancel**. Wpisz **nazwę nowego motywu**
i wybierz, od czego zaczyna:

- **Blank** – czysty motyw: `main.css`, `light-mode.css` i `dark-mode.css` tylko z nagłówkiem i pustymi
  sekcjami PART A / PART B. Do pisania motywu od zera.
- **Template** – kopia motywu oryginalnego (np. `custom-blue (original)`, zawsze w wersji dostarczonej
  z wtyczką) albo istniejącego motywu (jego zapisane pliki), z formularzem **zmiennych z części A**
  jego plików:
  - na górze **main.css** – sekcje pliku, np. *1. Shapes and sizes* (przyciski, duże przyciski
    formularzy, pola, karty: zaokrąglenia, wysokości, grubość napisów, wielkie litery),
    *2. Typography* (czcionka z listy czcionek zainstalowanych na większości komputerów – czcionki
    z innych serwisów blokuje polityka bezpieczeństwa), *3. Login page*, *4. Home page banner*;
  - niżej w **dwóch kolumnach light-mode.css | dark-mode.css** odpowiadające sobie sekcje obok siebie
    (łączone po numerze), np. *Colour scheme and palette*, *Brand colours* (kolor główny, hover,
    wciśnięcie, odcienie), *ResourceSpace tokens* (tła, formularze, komunikaty), *Theme colours*
    (tekst, linki, przyciski, tabele, strona logowania).

  Każde pole ma jako etykietę komentarz zmiennej z pliku, a pod nią nazwę zmiennej. Kolory mają obok
  wartości **próbnik koloru (picker)**; można też wpisać dowolną wartość CSS
  (`var(--colour-brand-primary-default)`, `rgba(0, 0, 0, .25)`). Grubość napisów i wielkie litery
  wybiera się z list. Pierwsze sekcje są rozwinięte, pozostałe rozwija kliknięcie.
*(od 1.27)* **Przełączniki** szablonu – reguły włączane i wyłączane przez `@media all` /
`@media not all` z komentarzem `SWITCH:` – są polami wyboru w sekcji, która o nich wspomina, np.
**Hide the local login fields** (10.1) w *3. Login page*. Ustawienia 0/1 (`…-own`) są listami.

Formularz powstaje z samych plików szablonu (sekcje, grupy i komentarze części A), więc działa też dla
własnych motywów o tym samym układzie (część A ze zmiennymi w `:root`, nagłówki sekcji `N. TYTUŁ`).
Zmieniają się tylko wartości w części A; reguły (część B), komentarze i dodatkowe pliki są kopiowane
bez zmian, a nazwa motywu w nagłówkach plików jest podmieniana. Wartości nie mogą zawierać `;`, `{`,
`}`, `<`, `>` ani komentarzy; `\` tylko przed kodem szesnastkowym, np. `"\2192"` *(od 1.26; wcześniej
bez `\`)*.

**Create** zapisuje nowy motyw jako wersję v1 i otwiera go w edytorze – sprawdź go przyciskiem
**Preview** i aktywuj w ramce Themes. Niezapisane zmiany w edytorze przepadają (strona najpierw pyta).
**Cancel** pokazuje z powrotem edytor.

### Konfigurator *(od 1.28)*

**Configurator** (nad edytorem, za **+ Add CSS file**) otwiera ten sam formularz dla **wybranego motywu**, wypełniony jego
wartościami (z zapisanych plików). Edytor i ramki po prawej są ukryte; na dole są przyciski **Save
theme** i **Cancel** (powrót do edytora).

- **Compare with** – oryginał, z którym motyw jest porównywany (domyślnie `custom-blue (original)`
  w wersji z bieżącej wtyczki), albo *nothing* (tylko zmienne motywu).
- Zmienne oryginału, których motyw **nie ma**, są oznaczone **missing in this theme** (na
  pomarańczowo; sekcje z nimi są rozwinięte; liczba jest na górze i w tytule sekcji). Wpisana jest
  wartość z oryginału; zaznacz **add to the theme** przy każdej, którą chcesz dodać – zmiana wartości
  zaznacza ją sama, **tick all missing** zaznacza wszystkie. Niezaznaczone nie są dodawane. Dodane
  zmienne trafiają do bloku `:root` sekcji o tym samym numerze w części A (np. sekcja 3 dla strony
  logowania), a gdy jej nie ma – do nowego bloku na końcu części A.
- **No rule of the theme uses it yet** – motyw jest starszy niż zmienna (np. odstępy, napis i ikona
  SSO z 1.26 w motywie z 1.25): samo dodanie zmiennej niczego nie zmieni. Formularz proponuje wtedy
  **replace the rules (PART B) of <plik> with the rules of the original** – wartości (część A) i Twoja
  sekcja „Extra rules” / „Your own rules” zostają, pozostałe zmiany reguł są zastępowane (stare pliki
  zostają w historii wersji). Tak samo rozwiązuje się brakujące przełączniki.
- Zmienne, które ma tylko motyw, są w **Other variables of this theme**.
- **Save theme** zapisuje wszystkie pliki motywu jako nową wersję (także gdy motyw jest aktywny –
  wtedy zmianę od razu widać w serwisie) i otwiera go w edytorze. Niezapisane zmiany w edytorze nie
  są używane (strona pyta przed otwarciem konfiguratora).

### Editor

Kolory edytora: Auto (dopasowane do strony), Light, Dark blue, Dracula, Monokai, Nord. Wybór
jest zapamiętywany w przeglądarce. Przy **Auto** edytor od razu zmienia kolory po kliknięciu
przełącznika jasny / ciemny *(od 1.22)*; wcześniej dopiero po przeładowaniu strony.

### Version history

- Każdy zapis tworzy nową wersję, w której są **wszystkie pliki motywu**, np.
  `v4_20261111_143012/` *(od 1.13; wcześniej każdy plik miał osobną historię)*.
  Przechowywanych jest 30 ostatnich wersji.
- **Load into editor** – wczytuje wszystkie pliki wybranej wersji do zakładek. Stają się
  bieżące dopiero po **Save**, jako nowa wersja.
- **Delete this version** *(od 1.03)* – usuwa wybraną wersję.
- **Delete all history** *(od 1.03)* – usuwa całą historię motywu poza najnowszą wersją.
- **Original themes > Restore original** – zastępuje wszystkie pliki motywu oryginałem
  z wtyczki (albo odtwarza motyw, jeśli został usunięty) i zapisuje to jako nową wersję.

### Light / dark mode switch

| Pole | Znaczenie |
|---|---|
| **Enable the switch** | Na każdej stronie pojawia się okrągły przycisk (prawy dolny róg), którym każdy użytkownik przełącza tryb jasny / ciemny – także wtedy, gdy żaden motyw nie jest aktywny *(od 1.17)*. |
| **Mode for users who have not chosen yet** | Tryb domyślny: zgodny z ustawieniem urządzenia, jasny albo ciemny. Przy wyłączonym przełączniku dotyczy wszystkich użytkowników *(od 1.13)*. |

Pola „Main theme”, „Light mode theme” i „Dark mode theme” z wersji 1.00–1.12 zostały usunięte
*(od 1.13)*. Pliki dla trybów są teraz częścią motywu.

**Jak działa przycisk** *(od 1.17)*:
- **Zalogowany użytkownik:** przycisk zapisuje jego preferencję wyglądu w ResourceSpace
  (`user_pref_appearance`: Light / Dark / Device default – to samo ustawienie co w preferencjach
  użytkownika). Wbudowany tryb ciemny ResourceSpace przełącza się od razu, bez przeładowania strony.
  Przycisk pokazuje tryb z tej preferencji, więc zmiana w preferencjach użytkownika też jest widoczna.
- **Aktywny motyw:** dodatkowo przełącza `light-mode.css` / `dark-mode.css`.
- **Bez aktywnego motywu:** działa sam tryb ciemny ResourceSpace.
- **Goście i strona logowania:** wybór jest zapamiętywany w ciasteczku przeglądarki (na rok)
  i dotyczy plików motywu.
- „Mode for users who have not chosen yet” dotyczy motywu i użytkowników bez zapisanej preferencji.

W wersjach 1.00–1.16 przycisk był widoczny tylko przy aktywnym motywie (od 1.13: motywie
z plikami obu trybów) i przełączał wyłącznie pliki motywu, zapisując wybór w ciasteczku.

## Przejście ze starszych wersji

Wersja 1.13 sama przekształca stary układ (jeden plik CSS na motyw), gdy po aktualizacji
zostanie otwarta dowolna strona serwisu albo strona opcji:

- Motywy wybrane w przełączniku, domyślnie `main-blue` + `light-blue` + `dark-blue`, tworzą motyw
  **`custom-blue`**, z zachowaniem Twoich zmian. Jeśli wybrane były inne motywy, powstaje motyw
  `migrated-switch`.
- Każdy inny stary motyw `X.css` staje się motywem `X`. Jego treść trafia do `main.css`, a pliki
  `light-mode.css` i `dark-mode.css` są puste.
- Aktywny motyw, stan przełącznika i tryb domyślny zostają zachowane.
- Stare pliki i ich historia wersji trafiają do katalogu `legacy-<data>` w katalogu danych wtyczki. Nic nie jest usuwane.
- Strona opcji pokazuje, co zostało przekształcone.

**Brakujące reguły po przekształceniu (naprawione w 1.15).** Jeśli na serwerze była wersja
wtyczki sprzed 1.06 (bez motywu `main-blue`), wersje 1.13–1.14 tworzyły `main.css` motywu
`custom-blue` z samym komentarzem. Brakowało wtedy przycisku SSO z tekstami, ukrywania
logowania lokalnego i ustawień baneru hero (np. `--cc-hero-height-desktop: 250px;` nie działało).

- Od 1.15 taki pusty `main.css` jest automatycznie zastępowany oryginałem i zapisywany jako
  nowa wersja. Plik z jakąkolwiek inną treścią nie jest zmieniany.
- Stare `light-mode.css` / `dark-mode.css` (dawne pełne motywy) mogą jeszcze zawierać starą
  regułę przycisku SSO. Reguły w `main.css` są od 1.15 silniejsze, więc przycisk i tak ma pełną
  szerokość. Pliki trybów można odświeżyć przyciskiem **Load original** nad edytorem, a potem
  **Save**.

## Zasady edycji motywów

1. **Najpierw Preview, potem Save.** Podgląd niczego nie zapisuje. Do sprawdzenia obu trybów
   przełącz Light mode / Dark mode obok adresu podglądu.
2. **Zmieniaj wartości zmiennych, a nie reguły.** Każdy plik ma dwie części *(od 1.05)*:
   - **część A – zmienne** (bloki `:root { … }`): kolory, rozmiary, teksty,
   - **część B – reguły**, które ich używają.
3. **Właściwy plik dla zmiany:**
   - wspólna dla obu trybów (kształt, rozmiar, czcionka, logowanie, baner) → `main.css`,
   - tylko kolory jednego trybu → `light-mode.css` lub `dark-mode.css`,
   - większy, osobny fragment (np. wygląd strony głównej) → dodatkowy plik.
4. **Ten sam kolor w obu trybach.** `light-mode.css` i `dark-mode.css` mają te same sekcje i nazwy
   zmiennych. Zmieniając np. `--cc-table-head-bg`, zrób to w obu plikach.
5. **Własne reguły dopisuj na końcu**, w sekcji „Extra rules” / „Your own rules”. Elementy, które
   w trybie ciemnym zostają jasne, sprawdź w przeglądarce (F12), skopiuj selektor i dodaj regułę
   w `dark-mode.css`.
6. **Własne warianty trzymaj w kopii motywu** (**Duplicate** albo **Create new theme**, np. `custom-green`).
   Oryginał `custom-blue` zawsze można przywrócić.
7. **Czcionki z innych serwerów nie zadziałają.** Polityka bezpieczeństwa (CSP) blokuje fonty
   z zewnętrznych adresów. Użyj czcionek systemowych albo umieść pliki fontów na serwerze.
8. **Kolory podawaj z `#`**, np. `#2563EB`.

## Najczęstsze zmiany – gdzie szukać

Ścieżki dotyczą motywu `custom-blue`. Numery sekcji są podane w komentarzach na początku
każdego pliku.

### Kolor główny (przyciski, aktywne elementy)

`light-mode.css` i `dark-mode.css`, sekcja 2:

```css
--colour-brand-primary-default: #2563EB; /* kolor główny */
--colour-brand-primary-hover:   #3B82F6; /* po najechaniu */
--colour-brand-primary-dark:    #1D4ED8; /* po wciśnięciu */
```

### Tło strony, nagłówka, kart

`light-mode.css`, sekcja 3 (`--colour-surface-*`). W `dark-mode.css` najprościej zmienić paletę
w sekcji 1 (`--dk-0` … `--dk-5`, `--dk-text*`), bo pozostałe wartości z niej wynikają.

### Kształt przycisków i pól

`main.css`, sekcja 1:

```css
--cc-cta-radius:   5px;  /* 0 = kwadrat, 999px = pigułka */
--cc-cta-weight:   600;  /* grubość napisu */
--cc-field-radius: 5px;  /* pola formularzy */
--cc-card-radius:  8px;  /* karty i panele */
```

### Czcionka

`main.css`, sekcja 2: `--cc-font`.

### Strona logowania – przycisk SSO i teksty *(od 1.05)*

`main.css`, sekcja 3. Przycisk SSO jest wyśrodkowany i ma szerokość pól logowania. *(od 1.27)*
Wszystkie ustawienia strony logowania są w tej jednej sekcji, w grupach: **3.1 Local login fields**
(przełącznik w sekcji 10.1, zob. niżej), **3.2 Spacing**, **3.3 SSO button**, **3.4 Label and
icon**, **3.5 Texts** – tak samo w formularzu **Create new theme** / **Configurator** (sekcja *3. Login page*, przełącznik
jako pole wyboru). Tylko kolory tytułu i tekstów zostają w `light-mode.css` / `dark-mode.css`,
sekcja 4.4, bo zależą od trybu.

```css
--cc-login-sso-title: "Single sign-on";   /* tytuł nad przyciskiem */
--cc-login-sso-text:  "Log in with ...";  /* tekst pod tytułem */
--cc-login-sso-note:  "Problems ...";     /* krótki opis pod przyciskiem */
```

- `""` daje pusty tekst.
- `none` w `--cc-login-sso-title` usuwa tytuł razem z tekstem.
- `none` w `--cc-login-sso-note` usuwa opis.
- Nowa linia w tekście: `\A`, np. `"Pierwsza linia\ADruga linia"`.
- Kolory tytułu i tekstów: `light-mode.css` / `dark-mode.css`, sekcja 4.4.

**Odstępy** *(od 1.26)* – przed 1.26 odstępy były stałe (16px wokół bloku, 12px między częściami):

```css
--cc-login-sso-space-before: 16px; /* nad blokiem, czyli nad tytułem „Single sign-on” */
--cc-login-sso-space-title:  12px; /* między tytułem / tekstem a przyciskiem */
--cc-login-sso-space-note:   12px; /* między przyciskiem a opisem pod nim */
--cc-login-sso-space-after:  16px; /* pod blokiem */
```

**Napis i ikona przycisku** *(od 1.26)* – domyślnie przycisk ma napis i ikonę klucza z ResourceSpace
(„Use SSO to log in”, w języku użytkownika). Własne:

```css
--cc-login-sso-label-own: 1;                   /* 0 = napis ResourceSpace, 1 = napis poniżej */
--cc-login-sso-label:     "Zaloguj kontem Microsoft";
--cc-login-sso-icon-own:  1;                   /* 0 = ikona klucza ResourceSpace, 1 = ikona poniżej */
--cc-login-sso-icon:      "\2192";             /* znak: "\2192" strzałka, "\1F511" emoji klucza, "" = brak */
--cc-login-sso-icon-gap:  8px;                 /* odstęp między ikoną a napisem */
```

Własny napis jest taki sam w każdym języku. Ikona to znak tekstowy (kod CSS `\XXXX` albo sam znak,
np. emoji); ma kolor napisu, emoji zachowują własne kolory. **Aktualizacja nie zmienia istniejących
motywów:** nowe zmienne i reguły są w oryginalnym `main.css` motywu `custom-blue` – przejmij je
przyciskiem **Load original** na zakładce `main.css` (Twoje zmiany tego pliku zostaną zastąpione;
zostają w historii wersji) albo utwórz nowy motyw z szablonu `custom-blue (original)` przez
**Create new theme** albo użyj **konfiguratora** *(od 1.28)*: pokazuje brakujące zmienne i może
przejąć nowe reguły.

### Ukrycie logowania kontami lokalnymi *(od 1.05)*

`main.css`, sekcja 10.1. Zmień `@media not all {` (pola widoczne, domyślnie) na `@media all {`
(pola ukryte).

- Ukrywane są pola użytkownika i hasła, „Keep me logged in”, przycisk „Log in” oraz linki
  „Request account” i „Forgotten password”.
- Pola znikają **tylko wtedy, gdy na stronie jest przycisk SSO**. Jeśli SSO zostanie wyłączone,
  nikt nie straci możliwości zalogowania.
- Ukrycie jest wyłącznie wizualne. Logowanie lokalne dalej działa, więc nie jest to
  zabezpieczenie.

### Wysokość baneru hero (slider na stronie głównej) *(od 1.05)*

`main.css`, sekcja 4. Obrazy slidera ustawia się w ResourceSpace (System > Slideshow).

```css
--cc-hero-height-desktop: 420px;      /* np. 300px, 50vh; 100vh = cały ekran */
--cc-hero-height-mobile:  240px;      /* ekrany węższe niż 820px */
--cc-hero-position:       center top; /* który fragment obrazu jest widoczny */
--cc-hero-content-offset: 0px;        /* odstęp treści od góry */
```

Granica „mobile” (819px) jest wpisana w regule `@media` w sekcji 11, bo zapytania `@media`
nie mogą korzystać ze zmiennych.

## Instalacja i pliki na serwerze

### Pobranie i instalacja

Wtyczka jest dystrybuowana jako archiwum `customcss-<wersja>.zip` na stronie wydań:
<https://github.com/s29829/custom-css/releases>. Archiwum zawiera katalog `customcss/` z kodem, licencją
i dokumentacją. Poniżej `<RS_ROOT>` to katalog instalacji ResourceSpace (np.
`/var/www/resourcespace`), a `www-data:www-data` to użytkownik i grupa, które uruchamiają PHP – przy Apache w RHEL, Rocky, Alma
czy Fedorze `apache:apache` (zob. „nginx czy Apache” niżej).

**Przez przeglądarkę:** Plugin Manager > Upload a plugin > wybierz plik `.zip` > Upload (przy
aktualizacji: **Overwrite**).

**Z wiersza poleceń (CLI)** – pobranie, rozpakowanie i uprawnienia:

```bash
VER=<wersja>                     # numer wersji ze strony wydań, np. 1.00
cd /tmp
curl -fLO https://github.com/s29829/custom-css/releases/download/v$VER/customcss-$VER.zip
sudo rm -rf <RS_ROOT>/plugins/customcss        # tylko przy aktualizacji (dane wtyczki są w filestore)
sudo unzip -q customcss-$VER.zip -d <RS_ROOT>/plugins/
sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
sudo find <RS_ROOT>/plugins/customcss -type d -exec chmod 755 {} \;
sudo find <RS_ROOT>/plugins/customcss -type f -exec chmod 644 {} \;
```

**Przez git:** repozytorium <https://github.com/s29829/custom-css> zawiera pełny katalog wtyczki.
Repozytorium nazywa się `custom-css` *(od 1.21; wcześniej `customcss`)*, ale katalog wtyczki musi
nazywać się `customcss` – stąd katalog docelowy w `git clone`:

```bash
sudo git clone https://github.com/s29829/custom-css <RS_ROOT>/plugins/customcss
sudo chown -R www-data:www-data <RS_ROOT>/plugins/customcss
```

Aktualizacja: `sudo git -C <RS_ROOT>/plugins/customcss pull`.

### nginx czy Apache

Wtyczka działa tak samo z oboma serwerami WWW. Różnią się tylko użytkownik uruchamiający PHP, nazwy
usług i kilka ustawień serwera – w poleceniach wyżej użyj użytkownika i grupy z tabeli:

| | nginx + PHP-FPM (Ubuntu / Debian) | Apache (Ubuntu / Debian) | Apache (RHEL, Rocky, Alma, Fedora) |
|---|---|---|---|
| Użytkownik:grupa uruchamiające PHP | `www-data:www-data` | `www-data:www-data` | `apache:apache` |
| Przeładowanie po aktualizacji | `sudo systemctl restart php8.3-fpm` | mod_php: `sudo systemctl reload apache2`; PHP-FPM: `sudo systemctl restart php8.3-fpm` | mod_php: `sudo systemctl reload httpd`; PHP-FPM: `sudo systemctl restart php-fpm` |

- **Kto uruchamia PHP:** przy Apache z mod_php – użytkownik Apache; przy PHP-FPM (nginx lub Apache) –
  użytkownik puli PHP-FPM (`user =` / `group =` w `/etc/php/<wersja>/fpm/pool.d/www.conf`, w rodzinie RHEL
  `/etc/php-fpm.d/www.conf`). Sprawdzenie: `ps -o user= -C php-fpm8.3,php-fpm,apache2,httpd | sort -u`.
- **Uprawnienia:** katalogi `755`, pliki `644`, właściciel = ten użytkownik i grupa (polecenia wyżej).
  Pliki wtyczki muszą być tylko czytelne; `<filestore>` musi być zapisywalny dla tego użytkownika – tak
  samo, jak wymaga tego ResourceSpace.
- **Bez listowania katalogów (Apache):** Apache w Ubuntu / Debian pozwala listować katalogi w `/var/www`
  (`Options Indexes` w `apache2.conf`). Wtyczka trzyma ustawienia w `<filestore>/<wtyczka>/data_<klucz>/`,
  a listing pokazałby nazwę tego katalogu. Wyłącz listowanie dla ResourceSpace (VirtualHost albo
  `apache2.conf` / `httpd.conf`) i przeładuj Apache:

  ```apache
  <Directory <RS_ROOT>>
      Options -Indexes +FollowSymLinks
  </Directory>
  ```

  nginx nie listuje katalogów, jeśli nie ustawiono `autoindex on`.
- **SELinux (rodzina RHEL):** po rozpakowaniu przywróć etykiety plików:
  `sudo restorecon -R <RS_ROOT>/plugins/customcss`. Filestore potrzebuje etykiety do zapisu (zwykle ustawionej
  przy instalacji ResourceSpace): `sudo semanage fcontext -a -t httpd_sys_rw_content_t "<filestore>(/.*)?"`
  i `sudo restorecon -R <filestore>`.

Na koniec: System > Plugins, aktywuj „Custom CSS” i kliknij **Options**. Wtyczka sama tworzy
swój katalog danych `<filestore>/customcss` – potrzebuje tylko prawa zapisu do filestore, które
ResourceSpace i tak wymaga.

- Wszystkie sposoby instalacji podmieniają tylko pliki wtyczki *(od 1.13)*. Motywy
  (od 1.16 w `<filestore>/customcss`), także Twoje zmiany w `custom-blue`, historia i ustawienia
  zostają.
- Nowa wersja oryginału trafia do `defaults/`. Przywraca się ją przyciskiem **Restore original**.
- W wersjach 1.00–1.12 aktualizacja nadpisywała motywy oryginalne w `<RS_ROOT>/Custom/themes`, a ich
  poprzednią treść zapisywały w historii.

| Ścieżka | Zawartość |
|---|---|
| `<filestore>/customcss/themes/<motyw>/` | Pliki motywu: `main.css`, `light-mode.css`, `dark-mode.css`, dodatkowe `*.css` (publiczne – ładuje je przeglądarka) |
| `<filestore>/customcss/data_<klucz>/settings.json` | Aktywny motyw, przełącznik, tryb domyślny |
| `<filestore>/customcss/data_<klucz>/versions/<motyw>/v<N>_<data>_<godz>/` | Wersje motywu (wszystkie pliki) |
| `<filestore>/customcss/data_<klucz>/legacy-<data>/` | Pliki ze starszych układów (po przekształceniu) |
| `<RS_ROOT>/plugins/customcss/defaults/<motyw>/` | Motywy oryginalne (do „Restore original”) |
| `<RS_ROOT>/plugins/customcss/lib/codemirror/` | Biblioteki edytora (CodeMirror 5) |

`<filestore>` to katalog `$storagedir` ResourceSpace (domyślnie `<RS_ROOT>/filestore`). Klucz
w nazwie `data_<klucz>` wynika z `scramble_key`, więc ustawień i historii nie da się odczytać,
zgadując adres. Motywy są podawane przeglądarkom przez `$storageurl`, tak jak logo z System >
Appearance.

**Katalogi wtyczka tworzy sama** przy pierwszym użyciu *(od 1.16)*: `filestore/customcss/themes`
i katalog danych, a potem motywy oryginalne. Potrzebuje tylko prawa zapisu do filestore, które
ResourceSpace i tak wymaga.

**Przeniesienie danych *(od 1.16)*.** W wersjach 1.00–1.15 dane były w `<RS_ROOT>/Custom/`.
Wersja 1.16 kopiuje je przy pierwszym otwarciu strony serwisu albo opcji:
- `Custom/themes` → `filestore/customcss/themes`,
- wersje, ustawienia i pliki `legacy-*` → katalog danych.

Stary katalog zostaje jako kopia zapasowa `Custom-moved-<data>` i można go usunąć. Gdy nie da
się go przemianować, zostaje pod starą nazwą. Wtyczka działa także zainstalowana
w `filestore/plugins` *(od 1.04)*.

## Reset wtyczki *(od 1.16)*

Panel **Reset plugin** na stronie opcji (po wpisaniu `RESET`) usuwa **wszystko, co wtyczka
zapisała** w `<filestore>/customcss`: wszystkie motywy (także własne), historię wersji
i ustawienia. Motywy oryginalne powstają ponownie, ale żaden motyw nie jest aktywny, dopóki go
nie aktywujesz. Tej operacji nie da się cofnąć. Pliki samej wtyczki nie są zmieniane.

## Licencja *(od 1.09)*

- Wtyczka jest udostępniona na licencji **PolyForm Noncommercial License 1.0.0** (plik
  [LICENSE](LICENSE)).
- **Bezpłatnie** można jej używać prywatnie i w celach niekomercyjnych, a także w organizacjach
  niekomercyjnych (edukacja, instytucje publiczne, organizacje charytatywne). Można ją zmieniać
  i udostępniać w tych celach, z zachowaniem tekstu licencji.
- **Użycie komercyjne** (firma, usługi dla klientów, płatny produkt) wymaga pisemnej zgody
  autora: <https://github.com/s29829>. Szczegóły: [LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md).
- Biblioteka CodeMirror 5 w `lib/codemirror/` ma własną licencję MIT (plik `lib/codemirror/LICENSE`).
