# invoice-document-preview Specification

## Purpose

Allow a user to inspect the document that a saved invoice would produce before downloading or emailing it, without changing the invoice or triggering delivery.

## Requirements

### Requirement: Saved Invoice Preview Availability

The system MUST provide an authorized user a preview of a saved invoice before delivery actions. The preview MUST represent the invoice's currently selected document layout and MUST be available from the invoice workflow alongside its document actions.

#### Scenario: Preview a saved invoice

- GIVEN a user can access a saved invoice with a selected document layout
- WHEN the user requests its preview
- THEN the system presents a rendering of that invoice using the selected layout

#### Scenario: No preview for unsaved edits

- GIVEN a user is creating or editing an invoice with unsaved changes
- WHEN the user requests a preview
- THEN the system does not present a document based on those unsaved changes

### Requirement: Preview Locale Fidelity

The system MUST render preview application-provided text, dates, and monetary presentation using the invoice's persisted locale and currency, independently of the active session locale. The preview MUST use the same invoice document content rules as its downloadable document.

#### Scenario: Spanish preview from English session

- GIVEN an invoice has persisted locale Spanish and the active session locale is English
- WHEN the user requests its preview
- THEN the preview's document text and dates are Spanish

#### Scenario: Preview matches selected layout

- GIVEN a saved invoice selects a supported layout
- WHEN the user requests its preview
- THEN the preview uses that layout's document presentation and invoice data

### Requirement: Non-Destructive Preview

The system MUST treat preview as read-only. Requesting a preview MUST NOT modify invoice data, status, totals, document locale, selected layout, timestamps, payment records, audit records, or delivery state; it MUST NOT send email, finalize the invoice, or create a downloadable document as a side effect.

#### Scenario: Preview leaves invoice unchanged

- GIVEN a saved invoice and its related records have known state
- WHEN the user requests a preview
- THEN all persisted invoice and related-record state remains unchanged

#### Scenario: Preview does not deliver

- GIVEN a saved invoice that has not been emailed
- WHEN the user requests a preview
- THEN no email is sent and its delivery state remains unchanged

### Requirement: Invalid Preview Requests

The system MUST reject a preview request for an unavailable invoice or unsupported document layout without exposing another invoice's data or performing side effects.

#### Scenario: Missing invoice

- GIVEN a preview request references no accessible saved invoice
- WHEN the request is made
- THEN the system returns the normal not-found or access-denied outcome and makes no state changes

#### Scenario: Unsupported layout

- GIVEN a saved invoice references an unsupported document layout
- WHEN the user requests a preview
- THEN the system rejects the preview and makes no state changes
