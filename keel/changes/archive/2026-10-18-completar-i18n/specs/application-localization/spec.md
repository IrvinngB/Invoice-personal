# application-localization Specification

## Purpose

Provide complete, consistent English and Spanish rendering across browser workflows, user feedback, invoice email, and invoice PDF documents, while preserving the distinct locale authorities for session UI and persisted invoices.

## Requirements

### Requirement: Complete Browser Localization

The system MUST render all user-facing browser text in the active session locale (`en` or `es`), including navigation, page titles, form labels, placeholders, actions, validation/help text, status/type labels, confirmations, and accessibility text. The system SHALL use matching English and Spanish messages for every text item it renders, and MUST leave user-entered business data unchanged.

#### Scenario: Spanish CRUD screen

- GIVEN the active session locale is Spanish
- WHEN a user opens any authenticated data-management screen
- THEN its application-provided text is rendered in Spanish

#### Scenario: English accessibility text

- GIVEN the active session locale is English
- WHEN a user views an interactive control with accessible text
- THEN that accessible text is rendered in English

#### Scenario: Stored data is not translated

- GIVEN a client name or invoice description was entered in either language
- WHEN the session locale changes
- THEN the stored client name or invoice description remains unchanged

### Requirement: Localized Browser Feedback

The system MUST present all application-generated browser feedback, including success, error, warning, and duplicate-prevention messages, in the active session locale.

#### Scenario: Localized successful action

- GIVEN the active session locale is Spanish
- WHEN a user completes an action that produces success feedback
- THEN the feedback is in Spanish

#### Scenario: Localized rejected action

- GIVEN the active session locale is English
- WHEN an action is rejected with application-generated feedback
- THEN the feedback is in English

### Requirement: Invoice-Locale Delivery Documents

The system MUST render invoice email and every supported invoice PDF layout in the locale persisted on that invoice, independently of the active session locale. Document labels, headings, status/type terms, and dates MUST use that locale; monetary values SHALL retain their invoice currency and locale-appropriate presentation.

#### Scenario: Spanish invoice sent from English session

- GIVEN an invoice has persisted locale Spanish and the active session locale is English
- WHEN the invoice email or PDF is generated
- THEN its application-provided document text and dates are Spanish

#### Scenario: English invoice document

- GIVEN an invoice has persisted locale English
- WHEN each supported PDF layout is generated
- THEN its application-provided document text and dates are English

#### Scenario: Locale independence

- GIVEN a saved invoice has a persisted locale
- WHEN the browser session locale changes before document generation
- THEN the invoice email and PDF locale do not change
