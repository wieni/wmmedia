# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WmMedia is a Drupal module that provides enhanced media management capabilities for Drupal 9/10/11. It extends Drupal's core media system with custom field types, widgets, and browsers for both images and files, featuring inline linking, usage tracking, and enhanced metadata handling.

## Development Commands

### PHP Code Quality
```bash
# Run coding standards checks and fixes
composer coding-standards

# Individual commands:
composer normalize                    # Normalize composer.json
php-cs-fixer fix --config=.php_cs.php # Fix PHP code style
```

### Frontend Assets (in assets/ directory)
```bash
cd assets/
npm run dev     # Development mode with watching
npm run build   # Production build
npm run build:webfont # Build webfont icons
```

## Architecture Overview

### Core Components

**Field Types (`src/Plugin/Field/FieldType/`)**
- `MediaFileExtras` - Enhanced file media field with extra title metadata
- `MediaImageExtras` - Enhanced image media field with alt/title overrides
- `MediaFileExtrasFieldItemList` - Field item list for media file extras

**Entity Browsers (`src/Plugin/EntityBrowser/Widget/`)**
- `MediaFileBrowser` - File selection interface 
- `MediaImageBrowser` - Image gallery selection interface
- `MediaBrowserBase` - Shared browser functionality

**Widgets (`src/Plugin/Field/FieldWidget/`)**
- `MediaFile` - File upload/selection widget
- `MediaWidget` - Base widget with common functionality

**Controllers**
- `GalleryController` - API endpoint for paginated media gallery (`/admin/api/media/paginate`)
- `UsageController` - Media usage tracking interface (`/media/{media}/usage`)

**Usage Tracking System**
- `UsageManager` - Core usage tracking service
- `UsageRepository` - Database operations for usage data
- `MediaUsageQueueWorker` - Background processing of usage updates
- Event subscribers automatically track media usage across entities

**Form Builders**
- `ImageOverviewFormBuilder` - Image gallery forms
- `FileOverviewFormBuilder` - File browser forms
- `OverviewFormBuilderBase` - Shared form building logic

### Event System

Multiple event subscribers handle automatic functionality:
- `UsageEntitySubscriber` - Tracks media usage when entities are saved
- `MediaDeleteSubscriber` - Cleanup on media deletion
- `ImageSubscriber` - Image-specific processing
- `MediaFormAlterSubscriber` - Form modifications
- `EntityFormDisplaySubscriber` - Display configuration changes

### Frontend Components

React-based gallery interface in `assets/components/gallery/`:
- `MediaGrid.js` - Grid layout component
- `MediaItem.js` - Individual media item display
- `MediaPreview.js` - Preview modal functionality

## Key Drupal Integrations

- Extends Drupal core media system
- Integrates with Entity Browser module for selection interfaces
- Provides CKEditor plugins for inline file linking
- Uses Drupal's queue system for usage tracking
- Follows Drupal coding standards via Wieni's wmcodestyle

## Testing

Check for existing test commands in the parent Drupal site's configuration. This module follows standard Drupal testing practices.