<?php

namespace Drupal\wmmedia;

use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\file\IconMimeTypes;

class Util
{

    public static function formatSize(int $size): string
    {
        if (class_exists(ByteSizeMarkup::class)) {
            return ByteSizeMarkup::create($size);
        }

        if (function_exists('format_size')) {
            // @phpstan-ignore-next-line
            return format_size($size);
        }

        throw new \LogicException('No function available to format size');
    }

    public static function fileIconClass(string $mimeType): string
    {
        if (class_exists(IconMimeTypes::class)) {
            return IconMimeTypes::getIconClass($mimeType);
        }

        if (function_exists('file_icon_class')) {
            // @phpstan-ignore-next-line
            return file_icon_class($mimeType);
        }

        throw new \LogicException('No function available to get file icon class');
    }

}
