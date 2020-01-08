wmmedia
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/wmmedia/v/stable)](https://packagist.org/packages/wieni/wmmedia)
[![Total Downloads](https://poser.pugx.org/wieni/wmmedia/downloads)](https://packagist.org/packages/wieni/wmmedia)
[![License](https://poser.pugx.org/wieni/wmmedia/license)](https://packagist.org/packages/wieni/wmmedia)

> A media library for Drupal 8, built by Wieni

## Why?
The wmmedia module provides custom fields, widgets and media browsers for both images (imgix) and files.
* Images don't use the core drupal image styles but imgix presets, these are both used backend and frontend.
* The image widget provides the possibility to overwrite image meta info (title, alt,description) on the field level.
* Inline linking of files inside the wysiwyg is supported in the media browser (<-> embedding files).
* Integration of media usage tracking (<-> file usage), the tracking includes in which entities and fields the media items are used.

## Installation

This package requires PHP 7.1 and Drupal 8.5 or higher. It can be
installed using Composer:

```bash
 composer require wieni/wmmedia
```

## How does it work?
* For images use the wmmedia image field.
* For files use the wmmedia widget on an entity reference field.

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE.md) file
for more information.
