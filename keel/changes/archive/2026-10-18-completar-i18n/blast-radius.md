# Blast Radius: Complete Application Localization and Invoice Preview

| Surface | Consumers found | Class | Locations | Required handling |
|---|---|---|---|---|
| Session UI locale | Every web route receives `SetLocale`; views/controllers resolve through it | AFFECTED | `bootstrap/app.php:14-17`; `SetLocale.php:15-25`; UI sources in design | Preserve session locale for UI, flashes, validation; never use invoice locale. |
| Translation catalogs | UI, PDF/email templates contain literals or call `__()` | INTERNAL | `resources/views/{auth,catalog,clients,invoices,recurring,settings,layouts,welcome}`; `lang/{en,es}/{app,invoice}.php` | Add matched keys; replace visible/ARIA/confirmation literals. |
| Invoice document locale | PDF service passes persisted template/locale and relations to four layouts; invoice columns persist settings | BREAKS | `PdfService.php:16-64`; `2026_10_05_000010_create_invoice_system_tables.php:111-113`; `resources/views/invoices/pdf/{classic,emerald,minimal,modern}.blade.php` | Keep renderer tied to `invoice.locale`; localize labels/dates/money without changing settings. |
| PDF download and audit behavior | Existing PDF route/action is linked by show, index, and dashboard and writes `pdf_downloaded` | AFFECTED | `routes/web.php:40`; `InvoiceController.php:360-368`; `resources/views/invoices/{show,index}.blade.php`; `resources/views/dashboard/index.blade.php:247` | Add a distinct GET preview route/action/button; do not alter download route or its audit log. |
| Preview response | Reuses PDF generation, filename, relations, page size, template | BREAKS | `PdfService.php:18-64,70-75`; `InvoiceController.php:360-367`; `Invoice.php:33-55` | Validate template; 404 unsupported/missing; no DB/audit/mail writes. |
| Email delivery | Action creates PDF, sends mail, updates status/sent_at and audits; mail has literals | BREAKS | `InvoiceController.php:370-393`; `InvoiceMail.php:24-42`; `resources/views/emails/invoice.blade.php:1-23` | Localize subject/body/date/amount with invoice locale; retain send/attachment/audit semantics. |
| Existing regression coverage | PDF service and workflow tests pin all-template generation, PDF route, and document settings | AFFECTED | `tests/Feature/PdfGenerationTest.php:16-66`; `tests/Feature/InvoiceWorkflowTest.php:115-118,308-342,344-385` | Extend or preserve these tests; add strict-TDD feature coverage for locale separation and preview non-mutation/fidelity. |

## Must-handle list

- Preserve session locale for UI, validation, and feedback while cataloging application-owned literals.
- Make all four PDF layouts and invoice mail resolve labels, dates, and money from persisted `invoice.locale`.
- Add `invoices.preview` as a read-only inline PDF route/button; keep `invoices.pdf` download auditing unchanged.
- Reject unsupported persisted preview templates rather than silently falling back, with no invoice, payment, audit, or mail mutation.
- Update existing PDF/workflow tests and add localization and preview tests before implementation.

## Published surfaces & compatibility

No external API, package, webhook, or serialized public contract found. The new route is internal; `invoices.pdf` and `invoices.send-email` remain compatible.

## Verdict: CONTAINED
