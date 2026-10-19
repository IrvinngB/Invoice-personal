# Structure

Last refreshed: 2026-10-18

```text
app/
  Console/Commands/       Scheduled application commands
  Http/Controllers/       Web request handlers
  Http/Middleware/        Request-wide concerns (authentication and locale)
  Mail/                   Mailable classes
  Models/                 Eloquent domain models
  Services/               Shared domain and document services
bootstrap/app.php         Middleware registration and app bootstrap
config/                   Laravel configuration
database/                 SQLite migrations, factories, and seeders
lang/{en,es}/             PHP translation catalogs
resources/
  css/                    Tailwind entry styles
  js/                     Vite JavaScript entry points
  views/                  Blade UI, email, and PDF templates
routes/web.php            All web routes
tests/{Feature,Unit}/     PHPUnit request/workflow and isolated-service tests
keel/                     Versioned SDD configuration, steering, capability specs, and changes
```

## Conventions

- Use Laravel resource controllers and conventional route names for CRUD; put one-off invoice workflow actions beside their resource routes in `routes/web.php`.
- Put Eloquent persistence and domain state in `app/Models`; place reusable calculations and document rendering in `app/Services`.
- Keep browser-rendered pages in `resources/views/<feature>/`; use `resources/views/layouts/app.blade.php` as the authenticated application shell.
- Place invoice layouts in `resources/views/invoices/pdf/` and mail markup in `resources/views/emails/`; keep preview requests in the invoice controller and reuse the PDF rendering service rather than creating a separate document template path.
- Add UI and document translation keys in matching `lang/en/*.php` and `lang/es/*.php` catalogs; locale codes are `en` and `es`.
- Add request and workflow coverage under `tests/Feature`; add pure calculation/service coverage under `tests/Unit`.
- Keep archived behavioral source-of-truth specifications in `keel/specs/<capability>/spec.md`; active change artifacts belong under `keel/changes/`.
