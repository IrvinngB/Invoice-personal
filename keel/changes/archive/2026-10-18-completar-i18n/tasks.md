# Tasks: Complete Application Localization and Invoice Preview

## Review Workload Forecast

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High

Estimated changed lines: 1,450–1,900 · Delivery strategy: auto-chain

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|---|---|---|---|
| 1–5 | Completed baseline | 1–5, ≤390 | Base: main. |
| 6 | Verify remediation | 6–7, ≤390 | Base: main; TDD. |

## Phase 1: Foundation — PR 1
- [x] 1.1 RED — Add `LocalizationTest.php` CRUD/feedback, English accessibility, and unchanged-data cases.
- [x] 1.2 GREEN — Localize `lang/{en,es}/{app,invoice,validation}.php`, shell/welcome/login, `AuthController.php`, and `BackupController.php`.
- [x] 1.3 REFACTOR — Consolidate keys; run `LocalizationTest`.

## Phase 2: Management Screens — PR 2
- [x] 2.1 RED — Add catalog/client/recurring/settings and validation-feedback assertions.
- [x] 2.2 GREEN — Localize management controllers and `catalog`, `clients`, `recurring`, and `settings` views.
- [x] 2.3 REFACTOR — Remove remaining literals; run localization tests.

## Phase 3: Invoice Browser — PR 3
- [x] 3.1 RED — Add session-locale invoice UI/feedback and PDF-audit tests.
- [x] 3.2 GREEN — Localize invoice/payment controllers and invoice views; add saved-invoice preview control.
- [x] 3.3 REFACTOR — Standardize invoice keys/ARIA; run focused tests.

## Phase 4: Invoice Documents — PR 4
- [x] 4.1 RED — Add opposite-locale mail/PDF, date/money, and layout tests.
- [x] 4.2 GREEN — Localize `InvoiceMail`, email, and four PDF views by `invoice.locale`.
- [x] 4.3 REFACTOR — Normalize formatting; run PDF/workflow tests.

## Phase 5: Inline Preview — PR 5
- [x] 5.1 RED — Add inline/locale/layout, invalid-layout, and no-mutation preview tests.
- [x] 5.2 GREEN — Add preview route/controller/service allowlist and inline shared rendering.
- [x] 5.3 REFACTOR — Separate preview/download paths; run preview and full tests.

## Phase 6: Remaining Browser Text — PR 6
- [x] 6.1 RED — In `tests/Feature/LocalizationTest.php`, assert Spanish dashboard and invoice `create`, `edit`, and `show` labels, placeholders, options, dates, actions, confirmations, and ARIA text.
- [x] 6.2 GREEN — Add matched `lang/{en,es}/{app,invoice}.php` keys and replace all application literals in `resources/views/dashboard/index.blade.php` and `resources/views/invoices/{create,edit,show}.blade.php`, retaining session locale/business data.
- [x] 6.3 REFACTOR — Literal-audit those views; run `php artisan test --filter=LocalizationTest`.

## Phase 7: Missing Document Scenarios — PR 7
- [x] 7.1 RED — In `tests/Feature/PdfGenerationTest.php`, assert English text/dates from each `classic`, `emerald`, `minimal`, and `modern` PDF.
- [x] 7.2 GREEN — Fix `PdfService.php` or `resources/views/invoices/pdf/{classic,emerald,minimal,modern}.blade.php` only for those failures. (No production change required: the new English assertions passed.)
- [x] 7.3 RED — In `InvoiceWorkflowTest.php`, change session locale and separately assert persisted-locale mail and PDF output.
- [x] 7.4 GREEN — Fix `InvoiceMail.php`, `PdfService.php`, or email view only for locale-independence failures. (No production change required: the persisted-locale assertions passed with a valid saved-invoice fixture.)
- [x] 7.5 RED — In `InvoicePreviewTest.php`, assert unsaved create/edit values cannot produce a document; English-session/Spanish-invoice preview has Spanish label/date/money; every layout has distinctive invoice content.
- [x] 7.6 GREEN — Fix only preview controller/service/views/PDF layouts required by 7.5, preserving inline/no-write behavior. (No production change required: preview already satisfies the new coverage.)
- [x] 7.7 REFACTOR — Snapshot audit rows, run PDF/workflow/preview tests, then `composer test`.

## Phase 8: Verification Remediation — PR 8
- [x] 8.1 RED — In `tests/Feature/LocalizationTest.php`, add a Spanish-session invoice-show assertion with an existing `AuditLog` event `pdf_downloaded` that expects its rendered audit label in Spanish; it must fail against the raw event-name formatter.
- [x] 8.2 GREEN — In `resources/views/invoices/show.blade.php` and matched `lang/{en,es}/app.php` keys, map application audit event identifiers (including `pdf_downloaded`) to localized labels while retaining unknown-event fallback behavior and the active session locale.
- [x] 8.3 RED — In `tests/Feature/InvoicePreviewTest.php`, replace the audit-count-only snapshot with the complete ordered persisted audit-row attributes before/after preview, and assert exact equality; it must prove the existing preview request creates or changes no audit record.
- [x] 8.4 GREEN — Make only the minimum preview-path correction required for 8.3 to pass, preserving its inline PDF response, no-mail behavior, and no invoice/payment writes. (Expected: no production change if the stricter snapshot passes.)
- [x] 8.5 REFACTOR — Run `php artisan test --filter=LocalizationTest`, `php artisan test --filter=InvoicePreviewTest`, then `composer test`; retain catalog parity and `git diff --check`.
