# Changelog - Custom CSS

Numeracja wtyczki: 1.00, 1.01, 1.02 … (+0.01 przy każdej zmianie, `tools/release.py bump`).
Wersja wtyczki w `customcss.yaml`; wersja całego repozytorium w pliku `VERSION` (zob. `CHANGELOG.md` w katalogu głównym).

## [1.14] - 2026-11-11
- Themes group: light-moge, dark-mode, main

## [1.13] - 2026-11-11
- Nowy model motywów: motyw to katalog z plikami main.css, light-mode.css, dark-mode.css i dowolnymi dodatkowymi plikami CSS; motywy main-blue, light-blue i dark-blue połączone w jeden motyw custom-blue.
- Edytor: zakładki plików nad edytorem (przełączanie, dodawanie i usuwanie dodatkowych plików), przycisk „Save all files (new version)” zapisujący wszystkie pliki razem jako nową wersję z datą i godziną, przycisk „Discard changes”, przyciski Undo/Redo (osobna historia dla każdej zakładki), podgląd z wyborem trybu jasny/ciemny.
- Przełącznik trybu: zostają „Enable the switch” i „Mode for users who have not chosen yet” (przy wyłączonym przełączniku tryb dla wszystkich); usunięte wybory motywu głównego, jasnego i ciemnego.
- Automatyczne przekształcenie starego układu (pliki Custom/themes/*.css) w katalogi motywów z zachowaniem zmian, aktywnego motywu i ustawień; stare pliki w Custom/legacy-<data>/. Instalatory nie nadpisują już motywów w Custom/themes.

## [1.12] - 2026-11-11
- Dokumentacja: instalacja z pobranego zipa skryptem rs-plugin-zip-install.sh (dołączany do każdego wydania).

## [1.11] - 2026-11-11
- Dokumentacja: adres pobierania wydań (github.com/s29829/customcss/releases), instalacja przez git clone z publicznego repozytorium wtyczki; instalator *-github-install.sh pobiera z repozytorium wtyczki bez tokenu.

## [1.10] - 2026-11-11
- Samodzielny pakiet zip (release.py package): plik LICENSE-COMMERCIAL.md w katalogu wtyczki, licencja i dokumentacja w paczce; instrukcja instalacji z archiwum zip.
- Pierwsze otwarcie strony opcji tworzy katalog Custom/themes (lub pokazuje, jak nadać uprawnienia) i brakujące motywy oryginalne – instalacja z zipa nie wymaga instalatora; motyw z historią wersji (usunięty celowo) nie jest odtwarzany.

## [1.09] - 2026-11-11
- Licencja PolyForm Noncommercial 1.0.0 (plik LICENSE; użycie komercyjne tylko za pisemną zgodą autora) oraz plik licencji MIT dla dołączonej biblioteki CodeMirror; sekcja „Licencja” w dokumentacji.

## [1.08] - 2026-11-11
- Dokumentacja: neutralny przykład nazwy motywu (dark-blue-custom) zamiast nazwy serwisu.

## [1.07] - 2026-11-11
- Dokumentacja w katalogu wtyczki: README.md (EN) i README.pl.md (PL), ze znacznikami wersji, od której dostępna jest każda funkcja.

## [1.06] - 2026-11-11
- Nowy motyw main-blue (część wspólna): kształty, rozmiary, czcionka, reguły przycisków/pól/tabel, strona logowania (SSO, ukrywanie logowania lokalnego) i baner hero; light-blue i dark-blue zawierają już tylko kolory i reguły zależne od trybu jasny/ciemny.
- Przełącznik light/dark: wybór motywu „Main theme” (domyślnie main-blue, można wyłączyć) – ładowany przed motywem jasnym/ciemnym oraz przed pojedynczym aktywnym motywem; przycisk przełącznika go nie usuwa, podgląd go zachowuje.

## [1.05] - 2026-11-11
- Motywy light-blue i dark-blue: wspólny układ sekcji (część A – zmienne, część B – reguły) i te same zmienne --cc-* w obu plikach; dark-blue korzysta teraz także ze wspólnych reguł przycisków, pól, tabel i czcionki.
- Strona logowania: przełącznik ukrywania pól logowania lokalnego (działa tylko, gdy jest przycisk SSO), przycisk SSO wyśrodkowany i na pełną szerokość pól, tytuł i tekst nad przyciskiem oraz krótki opis pod nim (zmienne --cc-login-sso-*).
- Strona główna: slider jako baner hero z wysokością dla desktop i mobile (zmienne --cc-hero-*, punkt przełączenia 819px).

## [1.04] - 2026-11-09
- Zgodność z wytycznymi ResourceSpace („Writing your own plugins”): katalog `src/plugins/CustomCSS` → `src/plugins/customcss` (nazwa katalogu = nazwa pliku YAML, układ 1:1 z serwerem).
- Adres bibliotek CodeMirror budowany przez `get_plugin_path()` (działa także dla wtyczki w `filestore/plugins`).
- `build/customcss-install.sh`: aktualne pliki wtyczki, `set -euo pipefail`, kopia oryginalnych motywów zapisywana jako `v<N>_<data>_<czas>.css` z bieżącym numerem wersji motywu.

## [1.03] - 2026-11-08
- Przycisk „Save as new version” pod edytorem – zapisuje motyw i tworzy kolejny numer wersji (nazwa pliku: numer wersji + data i godzina).
- Przyciski „Delete this version” (usuwa wszystkie pliki wybranej wersji) i „Delete all history” (usuwa całą historię poza najnowszym wpisem).

## [1.02] - 2026-11-08
- Historia wersji wyświetla numer wersji i datę, w kolejności chronologicznej (od najstarszej). Nowe pliki wersji: `v<N>_<RRRRMMDD>_<GGMMSS>.css`; starsze pliki bez prefiksu traktowane są jako v1.

## [1.01] - 2026-11-08
- Strona ustawień: tytuł wtyczki nad ścieżką nawigacji (breadcrumbs), jak na stronach admina ResourceSpace.
- Usunięty nagłówek „Settings”; sekcje w prawej kolumnie rozwijane (Themes – domyślnie otwarta, Editor, Version history, Light / dark mode switch).

## [1.00] - 2026-11-08
- Import istniejącej wersji wtyczki: edytor motywów CSS (CodeMirror), motywy `light-blue` / `dark-blue` w `Custom/themes`, historia wersji, przełącznik trybu jasny / ciemny.
