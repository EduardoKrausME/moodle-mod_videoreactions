# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry covers changes between its release tag and the immediately preceding published release, excluding later changes on `master`.

## 1.0.5 - 2026-10-23

### Changed

- Documented the required Moodle Video Bridge dependency (`local_video_bridge`, version `2026100600` or later) and the installation order in the README. Video Bridge is not bundled with this activity; its documented dependency chain currently also includes `local_ai_bridge`.
- Rebuilt the distributed AMD JavaScript at `amd/build/reactions.min.js`, replacing the earlier readable build with minified output. The comparison does not include changes to the `amd/src` source or establish a new reaction feature.
- Added concurrency configuration to the existing GitHub release workflow, grouping runs by Git reference without cancelling in-progress runs.
- Updated the plugin release identifier from `1.0.4` to `1.0.5` and its build number from `2026101501` to `2026102300`.

[Compare 1.0.4...1.0.5](https://github.com/EduardoKrausME/moodle-mod_videoreactions/compare/1.0.4...1.0.5)

## 1.0.4 - 2026-10-15

### Changed

- Added the plugin icon image at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.3` to `1.0.4` and its build number from `2026101500` to `2026101501`.

No functional changes to video reactions were identified between the published `1.0.3` and `1.0.4` tags.

[Compare 1.0.3...1.0.4](https://github.com/EduardoKrausME/moodle-mod_videoreactions/compare/1.0.3...1.0.4)
