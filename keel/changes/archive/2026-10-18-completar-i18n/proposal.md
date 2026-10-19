# Proposal: Complete Application Localization

## Intent

Complete English/Spanish localization and add a non-destructive invoice preview. UI follows session locale; documents/previews follow persisted invoice locale.

## Scope

### In Scope

- Complete matching `en`/`es` catalogs and localize remaining UI, feedback, mail, and four PDF templates.
- Format dates by applicable locale; leave business data unchanged.
- Let users preview a saved invoice's selected layout and locale before download/email, without state changes.
- Add focused localization and preview tests.

### Out of Scope

- New locales, third-party/client-side translation, or stored-data translation.
- Calculation, visual-template, authentication, or unrelated workflow changes.
- Preview of unsaved form edits, template editing, or preview side effects.

## Capabilities

> CONTRACT with the spec phase. One entry per spec file to create/update.

### New Capabilities

- `application-localization`: English/Spanish rendering rules for browser UI, user feedback, invoice email, and PDF documents.
- `invoice-document-preview`: Read-only pre-delivery rendering of a saved invoice in its selected template and persisted locale.

### Modified Capabilities

- None

## Approach

Extend Laravel catalogs and replace literals at rendering boundaries. Keep `SetLocale` for UI; use invoice locale for mail, PDFs, and preview. Preview reuses document inputs and never finalizes, sends, or changes its invoice. Test first, then convert surfaces.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `lang/{en,es}/*.php` | Modified | Matched UI, feedback, mail, and document keys. |
| `resources/views/**/*.blade.php` | Modified | Localized UI, email, and PDF rendering. |
| `app/Http/Controllers/*Controller.php` | Modified | Localized feedback and preview action. |
| `routes/web.php`, `app/Services/PdfService.php` | Modified | Preview route and shared document inputs. |
| `tests/Feature/*` | Modified/New | Locale and non-mutating preview coverage. |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Session and invoice locales are confused | Medium | Test each authority independently. |
| Preview differs or mutates state | Medium | Reuse document inputs; test no finalize/send. |
| Broad edit exceeds review budget | High | Split tasks into chained review units. |
| Blade/Alpine quoting breaks markup | Medium | Use escaped translation/JSON helpers and focused rendering tests. |

## Rollback Plan

Revert the catalog, route, view, controller, mail, PDF, and test changes together. Preview has no persisted effects; existing invoice data remains intact.

## Dependencies

- Existing Laravel localization catalogs and `SetLocale` middleware.

## Success Criteria

- [ ] Browser, mail, preview, and PDF surfaces render complete text for the proper locale.
- [ ] Saved-invoice preview reflects its selected template and locale, before download/email, without state changes.
- [ ] Tests prove session-locale UI/feedback and invoice-locale preview, email, and PDF output.
- [ ] Both catalogs contain every key used by the changed surfaces.
