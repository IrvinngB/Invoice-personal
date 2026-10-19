# Product

Last refreshed: 2026-10-18

Invoice is a single-user invoice studio for a small business or independent professional. It manages the full billing lifecycle from customer and catalog data through invoice issuance, payments, recurring documents, delivery, and data export/backup; its interface and generated documents support English and Spanish.

## Domain concepts

| Concept | Purpose |
| --- | --- |
| Business profile | Owner identity, defaults, branding, locale, and payment details. |
| Client | Billable customer with contact, currency, and locale preferences. |
| Catalog item | Reusable service or product with a unit price. |
| Invoice | Invoice, quote, or credit note with line items, totals, status, and document locale. |
| Payment | Recorded amount against an invoice, used to calculate outstanding balances. |
| Recurring invoice | Template/payload used to generate invoices on a schedule or on demand. |
| Audit log | Record of significant invoice lifecycle activity. |

## Current capabilities

- Single-user authentication and dashboard reporting.
- CRUD for clients and service/product catalog items.
- Invoice, quote, and credit-note creation; finalization, voiding, duplication, conversion, payment tracking, and email delivery.
- Recurring invoice generation and a scheduled command for recurring documents.
- Inline, read-only preview and PDF export with classic, emerald, minimal, and modern layouts; previews and downloadable documents follow the invoice's saved locale.
- CSV/JSON/SQLite backup and restore workflows.
- Complete English/Spanish browser localization, including feedback and accessibility text; invoice email and document localization remains independent of the browser session locale.

Versioned specifications cover application localization and invoice-document preview under `keel/specs/`.
