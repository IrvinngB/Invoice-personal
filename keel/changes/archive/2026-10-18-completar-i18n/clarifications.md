# Clarifications: Complete Application Localization and Invoice Preview

No BLOCKERs. Automatic mode records the following defaults as assumptions.

## ASSUMPTION — proceeding with default unless corrected

### Q1: Should the saved-invoice preview be an inline PDF rather than a separate HTML approximation?
- Targets: Requirement "Saved Invoice Preview Availability" and "Preview Locale Fidelity" (`specs/invoice-document-preview/spec.md`)
- Assumed: Yes — add a read-only GET preview endpoint that streams the same DomPDF output inline, in a new tab, using the persisted template, locale, currency, and page size. This makes the preview faithful to the downloadable document while avoiding a second rendering path. Evidence: `PdfService::generate()` is the sole document renderer and `InvoiceController::pdf()` already streams its output (`app/Services/PdfService.php:16-64`, `app/Http/Controllers/InvoiceController.php:360-368`).

### Q2: Does “all user-facing browser text” include framework-generated validation errors and their field attributes?
- Targets: Requirement "Complete Browser Localization" and "Localized Browser Feedback" (`specs/application-localization/spec.md`)
- Assumed: Yes — provide matching application validation messages and attribute labels for English and Spanish, as well as replacing view and controller literals. Evidence: form validation is extensively declared in controllers, while the repository currently contains only `lang/{en,es}/app.php` and `invoice.php`, so Spanish validation output otherwise falls back to framework/default text (`app/Http/Controllers/InvoiceController.php:83-112`, `lang/`).

### Q3: Which locale authority applies to date-only business fields in documents?
- Targets: Requirement "Invoice-Locale Delivery Documents" and "Preview Locale Fidelity" (`specs/application-localization/spec.md`, `specs/invoice-document-preview/spec.md`)
- Assumed: Format persisted issue/due dates as date-only values with Carbon’s `en`/`es` locale; do not apply timezone conversion. Evidence: the models persist `issue_date` and `due_date` as invoice data and existing Emerald PDF code localizes those dates without timezone conversion (`resources/views/invoices/pdf/emerald.blade.php:7-13`).

### Q4: Should preview activity remain absent from audit records as well as from invoice and delivery state?
- Targets: Requirement "Non-Destructive Preview" (`specs/invoice-document-preview/spec.md`)
- Assumed: Yes — preview creates no `AuditLog` row. Evidence: the existing download endpoint explicitly writes `pdf_downloaded`, and the preview requirement explicitly prohibits changes to audit records (`app/Http/Controllers/InvoiceController.php:360-368`, `app/Models/AuditLog.php:20-29`).

### Q5: What outcome should a preview request return if persisted template data is outside the supported allowlist?
- Targets: Requirement "Invalid Preview Requests" (`specs/invoice-document-preview/spec.md`)
- Assumed: Reject it with the normal not-found response and no side effects; preview MUST NOT silently fall back to Modern. Evidence: invoice create/update validation allows only `modern`, `classic`, `minimal`, and `emerald`, but `PdfService` currently falls back to Modern for a missing view (`app/Http/Controllers/InvoiceController.php:99-101`, `app/Services/PdfService.php:41-44`).

## Resolved

- R1: Preview applies only to saved invoices; unsaved create/edit form changes are not rendered. This is explicit in the preview specification’s "No preview for unsaved edits" scenario.
- R2: Browser UI remains controlled by `SetLocale` and documents remain controlled by the invoice locale. The implementation already separates these authorities (`app/Http/Middleware/SetLocale.php:17-23`, `app/Services/PdfService.php:19-21`).
