<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * traitement HTML.
 */
class CoreTextEditor {

    public static function &smilies(string $text): string {
        return $text;
    }

    public static function &text(string $text): string {
        return $text;
    }
}