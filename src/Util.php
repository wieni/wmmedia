<?php

namespace Drupal\wmmedia;

use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\file\IconMimeTypes;

class Util
{

    public static function formatSize(int $size): string
    {
        return ByteSizeMarkup::create($size);
    }

    public static function fileIconClass(string $mimeType): string
    {
        return IconMimeTypes::getIconClass($mimeType);
    }

}
