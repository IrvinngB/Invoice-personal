# Postmortem: Complete Application Localization and Invoice Preview

## Plan vs Reality

| Dimension | Planned | Actual | Delta | Why |
|-----------|---------|--------|-------|-----|
| Implementation size | 1,450–1,900 changed lines | 1,945 tracked changed lines, plus 425 lines in new application/test files (2,370 excluding SDD artifacts) | +470 above the upper estimate | Completing literal replacement across browser, mail, four PDF layouts, matched catalogs, and focused coverage required more view/catalog churn than forecast. |
| Delivery slices | 6–7 suggested PRs | 8 completed task units | +1–2 units | Verification uncovered two gaps: localized audit-event labels and a stricter preview audit-record snapshot. |
| Delivery mechanics | `stacked-to-main` | No commits, branches, or PRs were created; all implementation remains a working-tree diff | Planned review boundaries were not materialized | `apply-progress.md` records the boundaries, while `git log` contains no apply/archive-period commits and `git status --short` lists the entire change as modified/untracked. |
| Designed file scope | Controllers, renderer/mail, catalogs, views, routes, and focused feature tests | Same categories changed; drift audit reports 0 untracked/stale design items | No scope deviation | `drift.md` maps all implementation and test files to the design/task scope. |
| Spec proof | Session-locale UI; invoice-locale documents; inline non-mutating preview | 33 tests / 277 assertions passed, including all four layouts and exact audit-row snapshots | Complete | `verify-report.md` marks every compliance scenario PASS with no critical findings. |

## Prediction misses

- The 1,450–1,900-line estimate did not account sufficiently for parallel English/Spanish catalog entries and broad Blade literal replacement. The final implementation is 2,370 application/test lines of additions/deletions before SDD artifacts, based on `git diff --numstat` plus the four untracked application/test files.
- The delivery plan named `stacked-to-main`, but the planned PR boundaries remain documentary only. There are no commits between the 2026-10-18 apply/archive period, and the final state is uncommitted; this prevents the intended review-sized history from being reconstructed from Git.
- Direct PDF locale coverage initially used an incomplete invoice fixture; task 7.3 records that monetary totals had to be recalculated before the test represented a valid saved invoice. The correction was test-fixture-only, not a production defect.

## Proposed updates (require human approval — nothing is auto-applied)

- [ ] design template: add an explicit “catalog expansion estimate” line for every locale and namespace, including both translation entries and consuming-view edits, so multilingual literal sweeps are sized from surface counts rather than a generic line range.
- [ ] tasks template: require a delivery-evidence field for every planned chained/stacked work unit (commit SHA or explicit “uncommitted by agreement”), so a review strategy cannot be reported as complete without a traceable Git boundary.
- [ ] estimate calibration: for two-locale UI sweeps spanning PDFs and mail, use at least 2,300 changed application/test lines as the observed baseline; this change exceeded its upper estimate by 470 lines.

## Keep as-is

The locale-authority split was correctly designed and verified: session locale controls browser UI, while persisted invoice locale controls mail, PDFs, and preview. Strict TDD also exposed the audit-label and fixture-quality gaps before verification; `verify-report.md` confirms 33 passing tests and zero critical findings.
