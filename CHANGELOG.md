# Changelog

This changelog documents only the two latest **published** releases of `local_sanitizedclone`, verified on October 24, 2026 (America/Sao_Paulo). Each entry describes the changes between its published Git tag and the immediately preceding published release tag. Historical workflow and version-file changes mentioned below are documentation of the release comparisons, **not changes made by this changelog commit**.

## [1.0.4] - 2026-10-24

### Fixed
- Converted the administrative dashboard JavaScript from an ES-module `export const init` to an AMD `define([], function() { ... return {init}; })` entry point compatible with the plugin's Moodle module loading conventions.
- Updated both `amd/src/dashboard.js` and the distributed `amd/build/dashboard.min.js` file in this release. The existing operation-status highlighting for `blocked` and `failed` rows is preserved.
- Replaced `core_text::strlen()` with native `strlen()` for validating administrative clone-profile names in `index.php`; the 120-character limit remains unchanged.

### Maintenance and code quality
- Added the Moodle GPL license comment block to `templates/dashboard.mustache` and the JavaScript source; standardized boilerplate license links and header punctuation across PHP entry points, classes, language files, CLI scripts, and tests.
- Added PHPUnit test-method PHPDoc blocks in the integration, mapper, resume, and safety test files; existing test assertions remain unchanged.
- Added Git-ref-scoped concurrency configuration to the historical `.github/workflows/release.yml`, without cancelling in-progress release jobs.
- Updated `version.php` from plugin version `2026101500`, release `1.0.3`, to version `2026102400`, release `1.0.4`.
- No changes to the clone execution, mapping, safety-guard or sanitizer algorithms are present in the published tag comparison; their PHP diffs concern license boilerplate formatting.

**Full comparison:** [1.0.3...1.0.4](https://github.com/EduardoKrausME/moodle-local_sanitizedclone/compare/1.0.3...1.0.4)

## [1.0.3] - 2026-10-15

### Assets
- Added `pix/icon.png` as the plugin icon.

### Release metadata
- Updated `version.php` from plugin version `2026101402`, release `1.0.2`, to version `2026101500`, release `1.0.3`.
- No application logic files changed between the two published tags.

**Full comparison:** [1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-local_sanitizedclone/compare/1.0.2...1.0.3)
