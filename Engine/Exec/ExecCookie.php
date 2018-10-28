<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreRequestType;

/**
 * Outil de manipulation des cookies.
 *
 * @author Sébastien Villemain
 */
class ExecCookie
{

    /**
     * Création d'un cookie.
     *
     * @param string $name
     * @param string $content
     * @param int $timeLimit
     * @return bool Succès
     */
    public static function &createCookie(string $name,
                                         string $content,
                                         int $timeLimit = 0): bool
    {
        $rslt = false;

        if ($timeLimit >= 0) {
            $name = urlencode($name);
            $content = urlencode($content);
            $rslt = setcookie($name,
                              $content,
                              $timeLimit);
        }
        return $rslt;
    }

    /**
     * Destruction d'un cookie.
     *
     * @param string $name
     * @return bool Succès
     */
    public static function &destroyCookie(string $name): bool
    {
        $rslt = true;
        $name = urlencode($name);

        if (!empty(self::requestCookie($name))) {
            $rslt = setcookie($name,
                              '');
        }
        return $rslt;
    }

    /**
     * Retourne le contenu du cookie.
     *
     * @param string $name
     * @return string
     */
    public static function &getCookie(string $name): string
    {
        $name = urlencode($name);
        $content = self::requestCookie($name);
        $content = urldecode($content);
        return $content;
    }

    /**
     * Retourne le contenu brut du cookie.
     *
     * @param string $encodeName
     * @return string
     */
    private static function &requestCookie(string $encodeName): string
    {
        return CoreRequest::getString($encodeName,
                                      '',
                                      CoreRequestType::COOKIE);
    }
}