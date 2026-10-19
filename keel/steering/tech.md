# Tech

Last refreshed: 2026-10-18

## Stack

- PHP 8.2+ with Laravel 12 (`laravel/framework ^12.0`), Eloquent ORM, Blade, and PHPUnit 11 (`^11.5.50`).
- SQLite persistence with Laravel migrations, factories, and seeders.
- JavaScript ES modules, Vite 7 (`^7.0.7`), Tailwind CSS 4, Alpine.js 3, Chart.js, and SortableJS.
- DomPDF (`barryvdh/laravel-dompdf ^3.1`) for rendered invoice PDFs; `chillerlan/php-qrcode ^6.0` for QR generation.

## Settled decisions

- The application is a Laravel monolith: web routes reach controllers, which coordinate Eloquent models and small domain services. This keeps the single-user product simple and cohesive.
- Monetary amounts are stored and calculated as integer cents through `InvoiceCalculationService`, avoiding floating-point discrepancies in financial records.
- App UI translation strings live in matching `lang/en` and `lang/es` catalogs; `SetLocale` sets the request locale from the session, while each invoice retains its own locale for documents and number formatting. This keeps browser language independent from delivery-document language.
- Invoice PDFs are server-rendered Blade templates selected by a PDF service, keeping all printable layouts in the application and testable without a browser. The same rendering path produces inline, read-only previews so a preview faithfully reflects the selected layout without creating delivery side effects.

## Quality commands

- Full test suite: `composer test` (clears config, then runs `php artisan test`).
- Focused test: `php artisan test --filter=<filter>`.
- Front-end production build: `npm run build`.
- No configured lint or static-type-check command.

Strict TDD is enabled for the SDD workflow.
