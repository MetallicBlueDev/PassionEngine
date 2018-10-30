<?php

namespace PassionEngine\Engine\Exec;

/**
 * Outil de manipulation des URL.
 *
 * @author Sébastien Villemain
 */
class ExecUrl
{

    /**
     * Nettoie l'adresse URL du protocole.
     *
     * @param string $url
     * @return string
     */
    public static function &cleanUrl(string $url): string
    {
        $url = preg_replace('/https*:\/\//i',
                            '',
                            $url);
        return $url;
    }
}