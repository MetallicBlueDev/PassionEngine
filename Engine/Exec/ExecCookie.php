<?php

namespace PassionEngine\Engine\Exec;

use PassionEngine\Engine\Core\CoreRequest;
use PassionEngine\Engine\Core\CoreRequestType;
use PassionEngine\Engine\Core\CoreHtml;

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
     * @link http://php.net/manual/function.setcookie.php
     * @param string $name Le nom du cookie.
     * @param string $content La valeur du cookie.
     * @param int $timeLimit  Timestamp Unix, un nombre de secondes depuis l'époque Unix. Si 0, le cookie expirera à la fin de la session.
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

            if ($rslt && CoreLoader::isCallable('CoreHtml') && !CoreHtml::getInstance()->cookieEnabled()) {
                $rslt = false;
            }
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