# System importu transakcji bankowych

Zadanie rekrutacyjne dla Web24: import transakcji z CSV/JSON/XML, walidacja, historia importów + logi błędów.

Backend: Laravel 12 (REST API). Frontend: Vue 3 + Tailwind, SPA.

## Uruchomienie

Wymagane: PHP 8.2+, Composer, Node 18+, npm.

```bash
git clone https://github.com/monte-dev/w24-recruitment.git
cd w24-recruitment

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # Windows: New-Item -ItemType File -Path database/database.sqlite -Force

php artisan migrate
npm run build

php artisan serve
```

Aplikacja pod `http://localhost:8000`. Baza to SQLite, zero configu.

## Testy

```bash
php artisan test
```

73 testy, wszystkie warstwy (parsery, walidator, repozytorium, serwis, endpointy API, routing SPA).

## Przykładowe pliki

W `samples/` są gotowe pliki do przeciągnięcia w formularzu:
- `valid_transactions.{csv,json,xml}`: czysty import, status success
- `mixed_transactions.csv`: kilka dobrych rekordów + zły IBAN, ujemna kwota, zła waluta -> status partial

## Kilka nieoczywistych decyzji

**`transaction_id` nie ma UNIQUE.** Zadanie mówi "unikalny" tylko o kolumnie `id`. Poza tym przykładowe pliki testowe celowo mają te same transakcje w CSV/JSON/XML, więc z UNIQUE drugi upload by się wywalił. Produkcyjnie bym to oczywiście zabezpieczył (patrz niżej).

**Pusty plik = status `failed`, nie `success`.** 0 rekordów to nie sukces importu. Nic się nie wydarzyło, a zielony status mógłby kogoś zmylić, że dane jednak wleciały.

**Błąd w jednym wierszu nie wywala całego pliku.** Zła kwota w wierszu 500 z 1000 ląduje w `import_logs`, reszta się zapisuje normalnie, import dostaje status `partial`. Tylko błąd struktury całego pliku (zepsuty JSON, brak nagłówków CSV) rzuca 422 i przerywa cały import.

Więcej takich detali (parsowanie CSV z niezamkniętym cudzysłowem, obsługa BOM itp.) jest opisane bezpośrednio w testach jednostkowych. Łatwiej to zobaczyć na przykładzie niż w prozie.

## Co bym dodał, gdyby to szło na produkcję

- Kolejkę (queue) do parsowania większych plików w tle, zamiast trzymać usera na spinnerze
- Streamowanie plików (np. `league/csv`) zamiast wczytywania całości do pamięci
- Realną deduplikację po `transaction_id`
- Auth na endpointach (obecnie brak, zadanie tego nie wymagało)

## Architektura

- Strategy dla parserów (CSV/JSON/XML)
- DTO jako czysty nośnik danych między parserem a walidatorem
- Repository do bazy danych
- Cienki kontroler zasobowy z DI w konstruktorze.
- Logika biznesowa i transakcyjność zamknięta w serwisie.

## API

```
POST /api/imports          - upload pliku (multipart/form-data, pole "file")
GET  /api/imports          - lista importów
GET  /api/imports/{id}     - szczegóły + logi błędów
```

Przykład odpowiedzi po uploadzie:

```json
{
  "id": 1,
  "file_name": "mixed_transactions.csv",
  "total_records": 5,
  "successful_records": 2,
  "failed_records": 3,
  "status": "partial",
  "created_at": "2026-09-10T20:30:00.000000Z"
}
```

`GET /api/imports/{id}` zwraca to samo plus tablicę `logs` z `transaction_id` i `error_message` dla każdego odrzuconego rekordu.