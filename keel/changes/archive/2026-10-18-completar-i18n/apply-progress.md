# Apply Progress: Complete Application Localization and Invoice Preview

## Completed work

- Completed Phase 1, tasks 1.1–1.3 (Unit 1; stacked-to-main boundary).
- Added localization feature coverage for Spanish session UI and authentication feedback, English accessible text, validation attributes, and non-translated stored client data.
- Localized shared shell controls/navigation/errors/footer, the login page, authentication/backup feedback, catalog-backed validation messages, and the legacy welcome-page document title.
- Added matched `en`/`es` validation catalogs; the pre-existing invoice catalogs were already matched and were retained for the document-focused unit.
- Completed Phase 2 tasks 2.1–2.3 (Unit 2; stacked-to-main boundary): added Spanish management-screen and validation assertions plus English catalog-feedback coverage, then localized the remaining management views and formatting.
- Completed Phase 3 tasks 3.1–3.3 (Unit 3; stacked-to-main boundary): added invoice-browser session-locale and payment-feedback coverage, retained the PDF download audit assertion, localized invoice/controller feedback and key invoice browser controls, and added the saved-invoice preview control. The control intentionally points at the future read-only preview URL; its route and renderer remain Unit 5 work.
- Completed Phase 4 tasks 4.1–4.3 (Unit 4; stacked-to-main boundary): document mail and all four PDF layouts now use the saved invoice locale rather than the browser locale.
- Completed Phase 5 tasks 5.1–5.3 (Unit 5; stacked-to-main boundary): added a dedicated read-only invoice preview route that streams the existing DomPDF renderer inline, rejects unavailable templates, and does not create audit, mail, invoice, payment, or profile writes.
- Completed Phase 6 tasks 6.1–6.3 (Unit 6; stacked-to-main boundary): added Spanish session coverage for the dashboard and invoice create, edit, and show flows; localized their remaining application-owned labels, dates, option labels, placeholders, payment methods, and document actions.
- Completed Phase 7 tasks 7.1–7.7 (Unit 7; stacked-to-main boundary): closed the verification coverage gaps for all-English PDF layouts, locale independence after a session-locale change, and preview unsaved-state/locale/layout fidelity. The newly exercised behavior already met the contract, so this slice adds focused tests and no production changes.
- Completed Phase 8 tasks 8.1–8.5 (Unit 8; stacked-to-main boundary): localized application audit event labels by active session locale without changing stored event identifiers, and strengthened preview non-mutation coverage to compare every ordered persisted audit-row attribute before and after the request. Preview required no production correction.

## Files changed

- `tests/Feature/LocalizationTest.php`
- `lang/{en,es}/app.php`, `lang/{en,es}/validation.php`
- `app/Http/Controllers/{AuthController,BackupController}.php`
- `resources/views/{layouts/app,auth/login,welcome}.blade.php`
- `keel/changes/completar-i18n/tasks.md`
- `app/Http/Controllers/{CatalogItemController,ClientController,RecurringInvoiceController,SettingsController}.php`
- `resources/views/{catalog,clients,recurring,settings}/...`
- `app/Http/Controllers/{InvoiceController,PaymentController}.php`
- `resources/views/invoices/{create,edit,index,show}.blade.php`
- `tests/Feature/InvoiceWorkflowTest.php`
- `app/Mail/InvoiceMail.php`
- `resources/views/emails/invoice.blade.php`
- `resources/views/invoices/pdf/{classic,emerald,minimal,modern}.blade.php`
- `lang/{en,es}/invoice.php`
- `tests/Feature/PdfGenerationTest.php`
- `tests/Feature/InvoicePreviewTest.php`
- `routes/web.php`
- `app/Services/PdfService.php`
- `resources/views/invoices/show.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/invoices/{create,edit,show}.blade.php`
- `tests/Feature/{PdfGenerationTest,InvoiceWorkflowTest,InvoicePreviewTest}.php`

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1.1–1.3 | `tests/Feature/LocalizationTest.php` | Feature | `php artisan test --filter=InvoiceWorkflowTest` — 5 passed | `LocalizationTest` — 2 failed (missing Spanish accessible text and localized login feedback) | `php artisan test --filter=LocalizationTest` — 3 passed, 18 assertions | Spanish shell/login validation/failed+successful feedback; English accessible text; stored client name retained | Shared keys consolidated under `app`; validation attributes colocated with validation messages; focused test remains green |
| 2.1 | `tests/Feature/LocalizationTest.php` | Feature | `php artisan test --filter=LocalizationTest` — 3 passed | `LocalizationTest` — 2 failed (missing Spanish management headings; catalog store exposed nullable-description bug) | `php artisan test --filter=LocalizationTest` — 5 passed, 33 assertions | Spanish catalog/client/recurring/settings headings and Spanish catalog validation; English catalog success feedback | `php artisan view:cache` and focused test remain green |
| 2.2–2.3 | `tests/Feature/LocalizationTest.php` | Feature | `php artisan test --filter=LocalizationTest` — 5 passed | `LocalizationTest` — 1 failed (Spanish recurring-client picker missing) | `php artisan test --filter=LocalizationTest` — 5 passed, 38 assertions | Spanish recurring picker/frequency and detailed settings labels; English management catalogs and controller feedback remain covered | Replaced remaining management-surface literals with matched `app` keys; session-locale date/status formatting stays green |
| 3.1–3.3 | `tests/Feature/{LocalizationTest,InvoiceWorkflowTest}.php` | Feature | `php artisan test --filter='(LocalizationTest\|InvoiceWorkflowTest)'` — 10 passed | 2 failures: missing Spanish invoice-browser text/payment feedback and an incorrect audit column assertion | `php artisan test --filter='(LocalizationTest\|InvoiceWorkflowTest)'` — 11 passed, 107 assertions | Spanish invoice list/show/payment feedback; `invoices.pdf` still creates its `pdf_downloaded` audit event | Consolidated invoice UI/feedback under matched `app` keys; `php artisan view:cache` and `git diff --check` pass |
| 4.1–4.3 | `tests/Feature/{PdfGenerationTest,InvoiceWorkflowTest}.php` | Feature | `php artisan test --filter='(PdfGenerationTest\|InvoiceWorkflowTest)'` — 6 passed | 2 failed: Spanish invoice mail subject and localized PDF document date absent while app locale was English | `php artisan test --filter='(PdfGenerationTest\|InvoiceWorkflowTest)'` — 8 passed, 81 assertions | Opposite English app/Spanish invoice locale; all four layouts assert Spanish heading, date, and money; mail asserts Spanish subject/body/date/money | Replaced document literals with invoice catalog keys and normalized date-only output through locale-aware Carbon; `git diff --check` passes |
| 5.1–5.3 | `tests/Feature/InvoicePreviewTest.php` | Feature | `php artisan test --filter='(PdfGenerationTest\|InvoiceWorkflowTest)'` — 8 passed | `InvoicePreviewTest` — 3 failed because `invoices.preview` was undefined | `php artisan test --filter=InvoicePreviewTest` — 3 passed, 10 assertions; `composer test` — 28 passed, 205 assertions | Inline PDF for selected `classic`/Spanish invoice from English app locale; unsupported and missing invoices 404; preview leaves invoice/payment/audit state unchanged and sends no mail | Preview has a distinct named route and action; PDF download retains its audit-writing action. The preview passes an existing profile to the shared renderer so it cannot trigger the renderer's normal profile-creation fallback. |
| 6.1–6.3 | `tests/Feature/LocalizationTest.php` | Feature | `php artisan test --filter=LocalizationTest` — 6 passed | `LocalizationTest` — 1 failed: dashboard still rendered the English due-date prefix | `php artisan test --filter=LocalizationTest` — 7 passed, 65 assertions | Spanish dashboard types/dates/PDF action; create/edit reorder help, form labels, layouts, and tax option; show payment labels, methods, and email guidance | Replaced the remaining UI literals with matched `app` keys; localized dashboard date formatting through Carbon; `php artisan view:cache` and `git diff --check` pass. |
| 7.1–7.2 | `tests/Feature/PdfGenerationTest.php` | Feature/view | `php artisan test --filter='(PdfGenerationTest\|InvoiceWorkflowTest\|InvoicePreviewTest)'` — 11 passed | Added all-layout English document assertions; no behavior gap failed because layouts already resolve persisted `en` locale | Same focused run — 14 passed, 141 assertions | All four layouts: English heading, date, and USD presentation while app locale is Spanish | Extracted a shared rendered-layout helper; no production refactor needed |
| 7.3–7.4 | `tests/Feature/InvoiceWorkflowTest.php` | Feature/view | Focused document suite — 11 passed | Added a post-creation session switch and persisted Spanish mail/PDF assertions; direct PDF rendering initially exposed incomplete fixture totals | Valid saved-invoice fixture then focused suite — 14 passed, 141 assertions | Spanish invoice is unchanged by English→Spanish app-locale switch in mail and Modern PDF | Kept product code unchanged; fixture now represents normal saved invoice data |
| 7.5–7.7 | `tests/Feature/InvoicePreviewTest.php` | Feature/view | Focused document suite — 11 passed | Added absent create-preview endpoint plus Spanish content/date/money, invoice-data, and unique-layout-marker assertions for all four persisted templates | Focused suite — 14 passed, 141 assertions; `composer test` — 32 passed, 272 assertions | Unpersisted in-memory edit does not persist; English session/Spanish invoice retains Spanish document rules; classic/emerald/minimal/modern each render distinct selected-layout markers | Retained existing audit snapshot/no-mail checks; preview remains inline and read-only |
| 8.1–8.2 | `tests/Feature/LocalizationTest.php` | Feature/view | `php artisan test --filter='(LocalizationTest|InvoicePreviewTest)'` — 12 passed | `php artisan test --filter=LocalizationTest` — 1 failed: expected `PDF descargado`, raw formatter rendered `Pdf downloaded` | `php artisan test --filter=LocalizationTest` — 8 passed, 69 assertions | Spanish `pdf_downloaded` label; unknown `manual_reviewed` retains humanized fallback; stored `pdf_downloaded` identifier remains unchanged | Added matched event-label catalogs and a key-miss fallback in the audit view |
| 8.3–8.5 | `tests/Feature/InvoicePreviewTest.php` | Feature | `LocalizationTest` — 8 passed | Replaced count-only assertion with an existing audit record's complete ordered raw attributes; existing read-only preview passed the stricter exact snapshot | `php artisan test --filter=InvoicePreviewTest` — 5 passed, 45 assertions; `composer test` — 33 passed, 277 assertions | Existing audit row with payload/IP remains persisted; inline PDF and no-mail behavior remain covered | No preview-path change required; `git diff --check` passes |

## Deviations and issues

- The legacy Laravel `welcome` template is not routed by the application (`/` serves the dashboard). Its document title now follows the active locale; its stock Laravel marketing body is intentionally left outside application workflow localization.
- No pre-existing test failures. No migrations, schema changes, or document/preview behavior were touched.
- Unit 2 completed without deviation. Management-facing catalog, client, recurring, and settings text now resolves through matched English/Spanish keys; client and recurring browser dates/statuses use the active session locale. URL examples, ISO currency codes, language names, and paper-size standards remain literal data/identifiers.
- Unit 3 intentionally adds only the saved-invoice preview affordance (`/invoices/{id}/preview`) beside document actions. It does not register or implement that endpoint, so it cannot create audit, mail, invoice, or payment side effects before Unit 5.
- Unit 4 leaves email delivery, PDF attachment generation, invoice status, and download audit behavior unchanged; it only changes document presentation authority to the persisted invoice locale.
- Unit 5 adds `GET invoices/{invoice}/preview` as an inline PDF endpoint. It validates persisted templates against `PdfService::SUPPORTED_TEMPLATES`, returns 404 for unavailable templates or invoices, and deliberately avoids the download action's audit log. Existing PDF download and email flows remain unchanged.
- Unit 6 preserves the active session locale as the browser authority. Invoice locale remains stored document data and is not used by dashboard, form, or show-page UI formatting.
- Unit 7 found no product-code defect. The initial direct PDF assertion required complete monetary totals, so the workflow fixture now creates a normal invoice item and recalculates totals before rendering; this avoids treating malformed fixture data as a localization failure.
- Unit 8 found no preview implementation defect. The new audit snapshot includes a pre-existing record with all raw attributes, payload, IP, and ordered position; preview leaves it unchanged.

## Remaining work

- No apply tasks remain.

## PR boundary

Unit 1 is complete as the first `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 2 is complete as the second `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 3 is complete as the third `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 4 is complete as the fourth `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 5 is complete as the fifth `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 6 is complete as the sixth `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 7 is complete as the seventh `stacked-to-main` slice; no commit, branch, or PR was created by this executor. Unit 8 is complete as the eighth `stacked-to-main` slice; no commit, branch, or PR was created by this executor.
