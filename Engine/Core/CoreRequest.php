<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Fail\FailBase;
use PassionEngine\Engine\Fail\FailEngine;

/**
 * Gestionnaire de requêtes vers le protocole HTTP.
 *
 * @author Sébastien Villemain
 */
class CoreRequest
{

    /**
     * Mémoire tampon: tableau array des requêtes.
     *
     * @var array
     */
    private static $buffer = array();

    /**
     * Retourne la variable demandée de type booléenne.
     *
     * @param string $name Nom de la variable
     * @param bool $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return bool
     */
    public static function &getBoolean(string $name,
                                       bool $default = false,
                                       string $hash = 'default'): bool
    {
        return self::getMixed($name,
                              'BOOL',
                              $default,
                              $hash);
    }

    /**
     * Retourne la variable demandée de type entier.
     *
     * @param string $name Nom de la variable
     * @param int $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return int
     */
    public static function &getInteger(string $name,
                                       int $default = 0,
                                       string $hash = 'default'): int
    {
        return self::getMixed($name,
                              'INT',
                              $default,
                              $hash);
    }

    /**
     * Retourne la variable demandée de type virgule flottante.
     *
     * @param string $name Nom de la variable
     * @param float $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return float
     */
    public static function &getFloat(string $name,
                                     float $default = 0.0,
                                     string $hash = 'default'): float
    {
        return self::getMixed($name,
                              'FLOAT',
                              $default,
                              $hash);
    }

    /**
     * Retourne la variable demandée de type chaîne de caractères base64.
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    public static function &getBase64(string $name,
                                      string $default = '',
                                      string $hash = 'default'): string
    {
        return self::getMixed($name,
                              'BASE64',
                              $default,
                              $hash);
    }

    /**
     * Retourne la variable demandée de type chaîne de caractères d'un seul mot.
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    public static function &getWord(string $name,
                                    string $default = '',
                                    string $hash = 'default'): string
    {
        return self::getMixed($name,
                              'WORD',
                              $default,
                              $hash);
    }

    /**
     * Retourne la variable demandée de type chaîne de caractères.
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    public static function &getString(string $name,
                                      string $default = '',
                                      string $hash = 'default'): string
    {
        return self::getMixed($name,
                              'STRING',
                              $default,
                              $hash);
    }

    /**
     * Retourne le type de méthode utilisée pour la requête.
     *
     * @return string
     */
    public static function &getRequestMethod(): string
    {
        $hash = '_' . self::getString('REQUEST_METHOD',
                                      '',
                                      CoreRequestType::SERVER);
        return $hash;
    }

    /**
     * Retourne l'adresse de la page qui a conduit le client à la page courante.
     *
     * @return string
     */
    public static function &getHttpReferer(): string
    {
        $hash = self::getUnsafeString('HTTP_REFERER',
                                      '',
                                      CoreRequestType::SERVER);
        return $hash;
    }

    /**
     * Retourne la chaîne de requête utilisée pour accéder à la page.
     *
     * @return string
     */
    public static function &getQueryString(): string
    {
        return self::getString('QUERY_STRING',
                               '',
                               CoreRequestType::SERVER);
    }

    /**
     * Retourne le lien référent.
     *
     * @return string
     */
    public static function getRefererQueryString(): string
    {
        $queryString = self::getQueryString();
        return !empty($queryString) ? urlencode(base64_encode($queryString)) : '';
    }

    /**
     * Retourne la variable demandée de type chaîne de caractères (version non sécurisée).
     *
     * @param string $name Nom de la variable
     * @param string $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return string
     */
    private static function &getUnsafeString(string $name,
                                             string $default = '',
                                             string $hash = 'default'): string
    {
        return self::getMixed($name,
                              'UNSAFE-STRING',
                              $default,
                              $hash);
    }

    /**
     * Récupère, analyse et vérifie une variable URL.
     *
     * @param string $name Nom de la variable
     * @param string $type Type de donnée
     * @param mixed $default Donnée par défaut
     * @param string $hash Provenance de la variable
     * @return mixed
     */
    private static function &getMixed(string $name,
                                      string $type,
                                      $default = '',
                                      string $hash = 'default')
    {
        $rslt = null;


        if (isset(self::$buffer[$name])) {
            $rslt = self::$buffer[$name];
        } else {
            $input = self::getRequest($hash,
                                      true);

            if (isset($input[$name]) && $input[$name] !== null) {
                $rslt = self::protect($input[$name],
                                      $type);
                self::$buffer[$name] = $rslt;
            } else {
                $rslt = $default;
            }
        }
        return $rslt;
    }

    /**
     * Retourne le contenu de la requête demandée.
     *
     * @param string $hash
     * @param bool $useDefault
     * @return array
     */
    private static function &getRequest(string $hash,
                                        bool $useDefault): array
    {
        $hash = strtoupper($hash);
        $input = array();

        switch ($hash) {
            // no break
            case CoreRequestType::GET:
            case CoreRequestType::POST:
            case CoreRequestType::FILES:
            case CoreRequestType::COOKIE:
            case CoreRequestType::ENV:
            case CoreRequestType::SERVER:
                $input = CoreInfo::getGlobalVars($hash);
                break;
            default:
                $input = self::getDefaultRequest($hash,
                                                 $useDefault);
        }
        return $input;
    }

    /**
     * Retourne le contenu de la requête par défaut.
     *
     * @param string $hash
     * @param bool $useDefault
     * @return array
     * @throws FailEngine
     */
    private static function &getDefaultRequest(string $hash,
                                               bool $useDefault): array
    {
        $input = array();

        if (!$useDefault) {
            throw new FailEngine('invalid request method',
                                 FailBase::getErrorCodeName(9),
                                                            array($hash),
                                                            true);
        }

        $hash = self::getRequestMethod();

        if (!empty($hash)) {
            $input = self::getRequest($hash,
                                      false);
        }
        return $input;
    }

    /**
     * Vérifie le contenu des données importées.
     *
     * @param mixed $content
     * @param string $type
     * @return mixed
     */
    private static function &protect($content,
                                     string $type)
    {
        $type = strtoupper($type);

        switch ($type) {
            case 'INT':
                $content = self::protectInt($content);
                break;
            case 'FLOAT':
                $content = self::protectFloat($content);
                break;
            case 'BOOL':
                $content = self::protectBool($content);
                break;
            case 'BASE64':
                $content = self::protectBase64($content);
                break;
            case 'WORD':
                $content = self::protectWord($content);
                break;
            case 'STRING':
                $content = self::protectString($content);
                break;
            case 'UNSAFE-STRING':
                $content = self::protectString($content,
                                               true);
                break;
            default:
                CoreLogger::addException('CoreRequest : data type unknown');
                $content = self::protectString($content);
                break;
        }
        return $content;
    }

    /**
     * Force la valeur en type entier.
     *
     * @param mixed $content
     * @return int
     */
    private static function &protectInt($content): int
    {
        $matches = array();
        preg_match('/-?[0-9]+/',
                   (string) $content,
                   $matches);
        $content = (int) $matches[0];
        return $content;
    }

    /**
     * Force la valeur en type virgule flottante.
     *
     * @param mixed $content
     * @return float
     */
    private static function &protectFloat($content): float
    {
        $matches = array();
        preg_match('/-?[0-9]+(\.[0-9]+)?/',
                   (string) $content,
                   $matches);
        $content = (float) $matches[0];
        return $content;
    }

    /**
     * Force la valeur en type booléen.
     *
     * @param mixed $content
     * @return bool
     */
    private static function &protectBool($content): bool
    {
        $content = (string) $content;
        $content = (bool) (($content === '1' || $content === 'true') ? true : false);
        return $content;
    }

    /**
     * Force la valeur en chaîne de caractère en base 64.
     *
     * @param mixed $content
     * @return string
     */
    private static function &protectBase64($content): string
    {
        $content = (string) preg_replace('/[^A-Z0-9\/+=]/i',
                                         '',
                                         $content);
        return $content;
    }

    /**
     * Force la valeur en chaîne de caractère sur un mot.
     *
     * @param mixed $content
     * @return string
     */
    private static function &protectWord($content): string
    {
        $content = (string) preg_replace('/[^A-Z_]/i',
                                         '',
                                         $content);
        return self::protectString($content);
    }

    /**
     * Force la valeur en chaîne de caractère.
     *
     * @param mixed $content
     * @return string
     */
    private static function &protectString($content,
                                           $useUnsafeMethod = false): string
    {
        $content = trim((string) $content);

        if (!$useUnsafeMethod && preg_match('/(\.\.|http:|ftp:)/',
                                            $content)) {
            $content = '';
        }
        return $content;
    }
}