<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation des URL.
 *
 * @author Sébastien Villemain
 */
class ExecUrl {

    /**
     * Nettoie l'adresse web du protocole.
     *
     * @param string $url
     * @return string
     */
    public static function &cleanUrl(string $url): string {
        $url = preg_replace("/https*:\/\//i", "", $url);
        return $url;
    }
}