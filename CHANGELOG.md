# Changelog

All notable changes to this project will be documented in this file.

## 1.1.0 - 2026-11-18

### Changed

- Requires the 2.0 of the suite core, which runs on [laravel-privacy](https://github.com/jeffersongoncalves/laravel-privacy), the engine shared by the privacy suites. No change to this package's API, config or tables.

## 1.0.0 - 2026-11-16

First release: LGPD data protection impact assessments (RIPD, art. 38).

- `ImpactAssessment`: description, necessity, risks (likelihood x impact, LINDDUN threat, measures, residual scores), DPO opinion, linked to a laravel-lgpd-records processing activity.
- `approve()` schedules the next review; editing an approved assessment sends it back to draft.
- `LgpdRipd::candidates()`: activities with sensitive data or legitimate interest and no assessment.
- `php artisan lgpd:ripd [--json]` and `LgpdRipd::export()`.

## [Unreleased]
