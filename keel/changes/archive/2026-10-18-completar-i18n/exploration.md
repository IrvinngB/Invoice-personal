## Exploration: completar-i18n

### Current State
Laravel has `en` and `es` catalogs and a session-driven locale switcher: `SetLocale` accepts only those two locales and runs for all web requests. The dashboard and desktop navigation already use `app.*` keys, and the four PDF templates receive each invoice's persisted locale. The remaining UI is largely hard-coded: 17 of 23 Blade templates make no translation call, controller flash messages are literal English (with one Spanish duplicate-prevention message), and the email subject/body are literal English.

The existing catalogs are incomplete for the application surface. PDFs are inconsistent: classic/minimal/modern mostly use `invoice.*` but render English-style dates; emerald contains many inline `es ? ... : ...` strings rather than catalog keys. Existing tests verify that PDF bytes are generated for English and Spanish but do not assert translated output, locale switching, mail content, or translated notifications.

### Affected Areas
- `app/Http/Controllers/LocaleController.php`, `app/Http/Middleware/SetLocale.php`, `bootstrap/app.php` — request-locale persistence and validation.
- `lang/en/app.php`, `lang/es/app.php`, `lang/en/invoice.php`, `lang/es/invoice.php` — current catalogs; new UI, document, mail, and notification keys are needed in matched locales.
- `resources/views/{auth,catalog,clients,invoices,recurring,settings,layouts}/*.blade.php` — untranslated titles, labels, placeholders, actions, confirmations, status/type labels, accessibility text, and date presentation.
- `app/Http/Controllers/{Auth,Backup,CatalogItem,Client,Invoice,Payment,RecurringInvoice,Settings}Controller.php` — user-facing flash/error text is hard-coded.
- `app/Mail/InvoiceMail.php`, `resources/views/emails/invoice.blade.php` — subject and message body do not select the invoice locale.
- `app/Services/PdfService.php`, `resources/views/invoices/pdf/{classic,emerald,minimal,modern}.blade.php` — per-invoice PDF locale is passed, but all templates must use catalog strings and locale-aware dates consistently.
- `tests/Feature/InvoiceWorkflowTest.php`, `tests/Feature/PdfGenerationTest.php` and new focused feature tests — current coverage only proves rendering, not localized behavior.

### Approaches
1. **Complete the existing PHP catalogs and replace every user-facing literal at its rendering boundary** — retain Laravel's current `app.*`/`invoice.*` convention, add a small mail/notification namespace as needed, use translation calls in Blade/controllers/Mailable/PDF templates, and format dates with the selected UI or document locale.
   - Pros: follows existing architecture; translations stay reviewable; covers UI, flash feedback, mail, and all PDFs without a new dependency. Cons: broad mechanical edit across many templates. Effort: High.
2. **Translate only the currently localized dashboard, shell, and PDFs** — fill missing PDF keys and leave CRUD, settings, email, and flash text unchanged.
   - Pros: smaller diff. Cons: fails the project's stated English/Spanish UI and generated-document capability; leaves conspicuous mixed-language workflows. Effort: Medium.
3. **Use literal-string translation files or client-side translation state** — translate raw English strings at runtime rather than introducing semantic keys.
   - Pros: fewer initial key decisions. Cons: conflicts with the existing PHP catalog pattern, is brittle for interpolation/markup, and does not naturally cover mails or invoice-specific documents. Effort: High.

### Recommendation
Adopt approach 1. Preserve session locale for browser UI and invoice locale for durable documents, emails, monetary/date formatting, and ensure every new key exists in both catalogs. First add focused tests for locale switching, representative localized UI/flash output, localized mail subject/body, and each document-template locale; then convert the remaining views/controllers. Treat stored business/client/invoice content as user data, not translation keys.

### Risks
- The change spans all 23 views plus controllers and document surfaces, likely exceeding the 400-line review budget and requiring a chained implementation plan.
- Invoice locale and session locale serve different purposes; using the session locale for sent PDFs/emails would make already-issued document language non-deterministic.
- Translation calls embedded in Alpine/JavaScript strings need safe Blade/JSON escaping to avoid breaking client-side forms.
- Existing test assertions such as `Studio Overview` assume English and must explicitly set the locale or be updated to assert the appropriate translation.

### Ready for Proposal
Yes — the desired completion can be defined as complete English/Spanish coverage for user-facing browser UI, controller feedback, email, and all PDF templates, with deterministic locale-specific formatting and focused tests.
