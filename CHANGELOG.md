# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This changelog is incomplete. Pull requests with entries before 1.7.0
are welcome.

## [Unreleased]
### Added
- Add support for file media
- Add coding standard fixers
- Add issue & pull request templates
- Add ext-dom dependency

### Changed
- Make extra fields optional
  ([#2](https://github.com/wieni/wmmedia/issues/2))
- Refactor pretty much the whole codebase
- Apply some code style-related changes
- Update php & drupal/core dependencies
- Update .gitignore

### Fixed
- Fix issue with MediaFileExtras field list items in the media widget
- Update vulnerable npm dependencies

### Removed
- Remove unnecessary allowed_formats dependency
  ([#10](https://github.com/wieni/wmmedia/issues/10))
- Remove unnecessary dropzone_js dependency
  ([#8](https://github.com/wieni/wmmedia/issues/8))

## [1.7.20] - 2019-11-22
### Added
- Add bundle condition to media filter query

## [1.7.19] - 2019-10-18
### Added
- Add drupal/core composer dependency

### Changed
- Replace deprecated code usages
- Normalize composer.json

## [1.7.18] - 2019-09-25
### Fixed
- Fix issue with media widget & inline_entity_form

## [1.7.17] - 2019-09-24
### Fixed
- Fix broken media widget

## [1.7.16] - 2019-09-19
### Changed
- Make the media widget a real form item

## [1.7.15] - 2019-08-23
### Changed
- Remove some inline css

## [1.7.14] - 2019-07-26
### Changed
- Update node-sass
- Only remove actual references on media delete

## [1.7.13] - 2019-06-14
### Fixed
- Fix issue with media without file

## [1.7.12] - 2019-06-14
### Changed
- Update compiled assets

## [1.7.11] - 2019-06-14
### Changed
- Add more translations

## [1.7.10] - 2019-06-14
### Added
- Add module translations

### Changed
- Update parcel-bundler

## [1.7.9)] - 2019-05-13
### Added
- Allow rich text in the media widget description field

## [1.7.8)] - 2019-05-02
### Fixed
- Fix translations in media widget
- Fix issue when removing an item from the media widget

## [1.7.7)] - 2019-04-02
### Fixed
- Suppress getimagesize errors

## [1.7.6)] - 2019-03-12
### Fixed
- Fix notice

## [1.7.5)] - 2019-02-25
### Changed
- Update drupal/entity_browser dependency

## [1.7.4)] - 2019-02-18
### Changed
- Allow SVG's in the images entity browser

## [1.7.3)] - 2019-02-15
### Added
- Add SVG to allowed file extensions

## [1.7.2)] - 2019-02-15
### Added
- Set a default entity browser when creating a new media field

### Fixed
- Show fallback value in preview modal case the image has no dimensions

### Removed
- Remove an unused dependency on MediaWidget

## [1.7.1)] - 2019-02-04
### Fixed
- Don't show delete form warnings if the media field has no usages

## [1.7.0)] - 2019-01-21
### Added
- Add image field formatter
- Override default media view (`view.media.media_page_list`) with our
  custom gallery

### Changed
- Rename gallery theme hook implementation

## [1.0.0)] - 2018-02-06
Initial release
