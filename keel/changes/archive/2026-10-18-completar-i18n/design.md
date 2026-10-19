# Design: Complete Application Localization and Invoice Preview

## Technical Approach

Complete the existing Laravel PHP catalogs and replace application-owned literals at each Blade, controller, mail, and PDF rendering boundary. `SetLocale` remains the authority for browser UI and validation/flash feedback. Invoice documents explicitly use `$invoice->locale`, so a session change cannot alter a PDF, mail, or preview.

A saved-invoice preview is an inline DomPDF response. It reuses `PdfService::generate()` and the invoice’s persisted template, paper size, locale, relations, and money rules; it creates no alternate HTML rendering or stored state.

## Architecture Decisions

### Decision: Explicit translation namespaces and locale parameters
**Choice**: Extend matching `app.php`/`invoice.php` catalogs and add matched validation messages; call `__()` in UI/controller context and `__($key, [], $invoice->locale)` in mail/PDF context.
**Alternatives considered**: Raw-string catalogs; temporarily changing the global application locale for documents.
**Rationale**: This follows the existing catalogs and prevents queued mail/PDF output from inheriting session state.

### Decision: Preview streams the shared PDF renderer inline
**Choice**: Add `GET invoices/{invoice}/preview`, validate the persisted template against the four supported names, then return `PdfService::generate($invoice)->stream($filename)` without audit logging.
**Alternatives considered**: A separate Blade preview; client-side PDF generation; reusing the download route.
**Rationale**: Shared rendering guarantees fidelity; a dedicated GET action preserves download audit semantics and has no delivery/state side effects.

### Decision: Locale-aware dates are formatted in document views
**Choice**: Standardize PDF and mail date-only fields through Carbon’s `locale($locale)->isoFormat(...)`; retain `InvoiceCalculationService::formatMoney(..., $locale)`.
**Alternatives considered**: Store translated dates; apply timezone conversion.
**Rationale**: Dates are persisted business dates, while presentation belongs to their document locale.

## Data Flow

```text
browser session -> SetLocale -> Blade/controllers/validation -> app locale
saved invoice -> preview/pdf/email -> PdfService or InvoiceMail
             -> invoice.locale -> catalogs + Carbon/money formatting -> document
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `routes/web.php` | Modify | Register the read-only named preview route before document actions. |
| `app/Http/Controllers/InvoiceController.php` | Modify | Add preview action; localize invoice feedback and duplicate validation. |
| `app/Http/Controllers/AuthController.php`, `BackupController.php`, `CatalogItemController.php`, `ClientController.php`, `PaymentController.php`, `RecurringInvoiceController.php`, `SettingsController.php` | Modify | Replace application feedback literals. |
| `app/Services/PdfService.php` | Modify | Keep one renderer and expose/centralize supported-template selection used by preview. |
| `app/Mail/InvoiceMail.php`, `resources/views/emails/invoice.blade.php` | Modify | Pass/use invoice locale for subject, body, dates, and amounts. |
| `lang/en/app.php`, `lang/es/app.php`, `lang/en/invoice.php`, `lang/es/invoice.php`, `lang/en/validation.php`, `lang/es/validation.php` | Modify/Create | Matched UI, feedback, mail, document, field-attribute, and validation keys. |
| `resources/views/auth/login.blade.php`, `resources/views/catalog/{create,edit,index}.blade.php`, `resources/views/clients/{create,edit,index,show}.blade.php`, `resources/views/invoices/{create,edit,index,show}.blade.php`, `resources/views/recurring/{create,index}.blade.php`, `resources/views/settings/index.blade.php`, `resources/views/welcome.blade.php`, `resources/views/layouts/app.blade.php` | Modify | Translate all application-owned visible, confirmation, placeholder, title, and accessibility text. Add Preview beside PDF/email on the saved invoice show page. |
| `resources/views/invoices/pdf/{classic,emerald,minimal,modern}.blade.php` | Modify | Remove inline bilingual literals; use catalogs and consistent invoice-locale date/money rendering. |
| `tests/Feature/LocalizationTest.php`, `tests/Feature/InvoicePreviewTest.php` | Create | TDD coverage for locale authority, feedback/mail/PDF output, preview authorization/fidelity/no mutation. |
| `tests/Feature/PdfGenerationTest.php`, `tests/Feature/InvoiceWorkflowTest.php` | Modify | Expand all-template localization coverage and retain document workflow regression coverage. |

## Interfaces / Contracts

```php
GET /invoices/{invoice}/preview
// name: invoices.preview; returns inline application/pdf
// 404 for missing invoice or unsupported persisted template; no writes
```

## Testing Strategy

| Layer | What to test | Approach |
|-------|--------------|----------|
| Feature | Spanish/English representative CRUD UI, flashes, and framework validation attributes | Session locale requests and response/session assertions. |
| Feature | Mail/PDF text and dates honor invoice locale despite opposite session locale | `Mail::fake()`/mailable render; DomPDF output/rendered-view assertions for all four templates. |
| Feature | Preview PDF, selected layout/locale, no invoice/payment/audit/email mutation, invalid template/missing invoice | Snapshot relevant DB rows/counts before and after GET; assert inline PDF response and 404 cases. |

## Migration / Rollout

No migration required. Existing `locale`, `pdf_template`, and `page_size` columns supply all preview/document inputs. Deploy catalogs, renderer checks, route, views, and tests together.

## Rollback & Reversibility

- No schema/data migration or backfill; rollback is reversible by reverting this change.
- No feature flag: remove the preview route/button to disable it; existing download/email remain unchanged.
- Preview persists nothing, so no late-revert data loss. The deploy owner reverts the application release and verifies PDF download/email plus both locale UI paths.

## Observability

- Do not create audit records or application logs for preview, per its read-only contract. Unexpected renderer/template failures retain Laravel’s normal exception reporting with request context.
- Monitor web-server 5xx rate for `invoices.preview` and PDF rendering; success is preview responses returning inline PDFs with no correlated invoice/audit writes.

## Open Questions

- [ ] None.
