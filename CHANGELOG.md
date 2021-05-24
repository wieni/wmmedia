# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This changelog is incomplete. Pull requests with entries before 1.7.0
are welcome.

### Added
- Add _Media gallery (thumbnail)_ and _Media gallery (large)_ image styles. 

### Changed
- Change the Image media type source from `imgix` to the core `image` source.
- Replace `field_media_imgix` with `field_image`
- Replace hardcoded field references with dynamic references to the current media source field

### Removed
- Remove `wieni/imgix` dependency
- Remove `wmmedia_image` theme hook. Use `image_style` instead.
- Remove `MediaWidgetRenderEvent` without replacement.

## [2.0.20] - 2021-05-17
### Fixed
- Fix not being able to click on image browser toggles

## [2.0.19] - 2021-05-17
### Changed
- Apply security updates for `lodash`, `hosted-git-info` & `elliptic`

### Fixed
- Disable preprocessing & minification for compiled assets

## [2.0.18] - 2021-04-27
### Changed
- Show add image button before the existing images table when prepending

## [2.0.17] - 2021-04-26
### Fixed
- Allow prepending items using the field widget

## [2.0.16] - 2021-02-22
### Fixed
- Fix double urlencode of destination query param 🙈

## [2.0.15] - 2021-02-22
### Fixed
- Fix redirects to '/admin/api/media/paginate' when editing images through the media content overview

## [2.0.14] - 2021-01-18
### Added
- Add media widget styles
- Add compatibility with Gin, Claro & Seven themes
  
### Changed
- Clean up existing theming
- Replace yarn with npm

### Removed
- Remove unnecessary base field override configs

## [2.0.13] - 2020-10-15
### Removed
- Stop automatically opening linked media in new tab/window

## [2.0.12] - 2020-07-24
### Removed
- Remove classy dependency (see [#3115088](https://www.drupal.org/project/drupal/issues/3115088))

## [2.0.11] - 2020-07-23
### Removed
- Remove hook_event_dispatcher dependency

## [2.0.10] - 2020-07-10
### Fixed
- Prevent errors when entities are deleted during update hooks before the
 wmmedia_usage schema is installed.

## [2.0.9] - 2020-05-07
### Fixed
- Fix entity browser widget with cardinality > 1 never showing checkboxes

## [2.0.8] - 2020-05-06
### Fixed
- Fix deletion bug and allow generation by entity type.

## [2.0.7] - 2020-03-09
### Changed
- Allow uninstalling the file media type by adding an access check to file 
media routes that makes sure the bundle exists

## [2.0.6] - 2020-02-17
### Fixed
- Remove lingering link attributes in ckeditor when changing existing link.

## [2.0.5] - 2020-02-03
### Changed
- Make MediaFileExtras::getMedia nullable to prevent errors when
  referenced entity is removed

## [2.0.4] - 2020-01-24
### Added
- Add svg version of media_file_link icon

### Changed
- Improve quality of 16x16 media_file_link icon

## [2.0.3] - 2020-01-22
### Changed
- Change getMedia return type to nullable
- Change MediaDeleteSubscriber to work with all media with file sources

## [2.0.2] - 2020-01-08
### Changed
- Update entity browser configs

### Fixed
- Ensure usage manager doesn't crash on empty values

### Removed
- Remove implicit dropzonejs dependency

## [2.0.1] - 2020-01-08
### Fixed
- Fix undefined arrays in usage manager

## [2.0.0] - 2020-01-08
### Added
- Add support for file media including wysiwyg inline linking
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
