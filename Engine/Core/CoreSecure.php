<?php

namespace PassionEngine\Engine\Core;

use Throwable;
use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Fail\FailBase;
use PassionEngine\Engine\Fail\FailEngine;

/**
 * Gestionnaire de la sécurité du noyaux.
 * Inclus un système de sécurité, une analyse rapidement les données reçues et une configuration les erreurs.
 *
 * @author Sébastien Villemain
 */
class CoreSecure
{

    /**
     * Retourne la liste des caractères interdits.
     *
     * @var array
     */
    private const BAD_QUERY_STRINGS = array(
        'select',
        'union',
        'insert',
        'update',
        'and',
        '%20union%20',
        '/*',
        '*/union/*',
        '+union+',
        'load_file',
        'outfile',
        'document.cookie',
        'document.write',
        'onmouse',
        '<script',
        '<iframe',
        '<applet',
        '<meta',
        '<style',
        '<form',
        '<img',
        '<body',
        '<link',
        '<comment',
        '..',
        'http://',
        'https://',
        'ftp://',
        '%3C%3F');

    /**
     * Instance de cette classe.
     *
     * @var CoreSecure
     */
    private static $secure = null;

    /**
     * Verrouillage de la sécurité (exception, erreur critique).
     *
     * @var bool
     */
    private $locked = false;

    /**
     * Activation du mode DEBUG.
     *
     * @var bool
     */
    private $debuggingMode = false;

    /**
     * Routine de sécurisation.
     */
    private function __construct()
    {
        $this->configureOutput();
        $this->checkServerQueryString();
        $this->checkServerRequest();
        $this->checkGlobals();

        // Attention: il ne faut pas définir l'index avant CoreInfo mais avant CoreLoader
        if (!defined('PASSION_ENGINE_BOOTSTRAP')) {
            $this->locked = true;
            define('PASSION_ENGINE_BOOTSTRAP',
                   true);
        }

        $this->debuggingMode = CoreRequest::getBoolean('debuggingMode',
                                                       false,
                                                       CoreRequestType::GET);
    }

    /**
     * Retourne l'instance du gestionnaire de sécurité.
     *
     * @return CoreSecure
     */
    public static function &getInstance(): CoreSecure
    {
        self::checkInstance();
        return self::$secure;
    }

    /**
     * Vérification de l'instance du gestionnaire de sécurité.
     *
     * @throws FailEngine
     */
    public static function checkInstance(): void
    {
        if (self::$secure === null) {
            self::$secure = new CoreSecure();

            // Si nous ne sommes pas passé par l'index
            if (self::$secure->locked()) {
                throw new FailEngine('invalid access',
                                     FailBase::getErrorCodeName(10));
            }
        }
    }

    /**
     * Vérifie si le mode DEBUG est actif.
     *
     * @return bool
     */
    public static function &debuggingMode(): bool
    {
        $rslt = false;

        if (self::$secure !== null) {
            $rslt = self::$secure->debuggingMode;
        }
        return $rslt;
    }

    /**
     * Détermine si il y a verrouillage de la sécurité (risque potentiel).
     *
     * @return bool
     */
    public function &locked(): bool
    {
        return $this->locked;
    }

    /**
     * Affiche un message d'erreur au client, mettra fin à l'exécution du moteur.
     * Cette fonction est activé si une erreur est détectée.
     *
     * @param Throwable $ex L'exception interne levée.
     */
    public function catchException(?Throwable $ex)
    {
        $this->locked = true;

        if ($ex === null) {
            $ex = new FailEngine('generic error');
        }

        // Préparation du template debug
        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString('errorMessageTitle',
                                    $this->getErrorMessageTitle($ex));
        $libMakeStyle->assignArray('debugMessages',
                                   $this->getDebugMessages($ex));

        // Affichage du template en debug si problème
        $libMakeStyle->display('debug',
                               true);

        // Arret du moteur
        exit();
    }

    /**
     * Retourne le type d'erreur courant sous forme de message.
     *
     * @param Throwable $ex L'exception interne levée.
     * @return string
     */
    private function &getErrorMessageTitle(Throwable $ex): string
    {
        if ($ex instanceof FailBase && $ex->useArgsInTranslate()) {
            $errorMessageTitle = CoreTranslate::getConstantDescription(FailBase::getErrorCodeName($ex->getCode()),
                                                                                                  $ex->getFailArgs());
        } else {
            $errorMessageTitle = CoreTranslate::getConstantDescription(FailBase::getErrorCodeName($ex->getCode()));
        }

        if (empty($errorMessageTitle)) {
            $errorMessageTitle = 'Stop loading';

            if ($this->debuggingMode) {
                $errorMessageTitle .= ': ' . $ex->getMessage();
            }
        }
        return $errorMessageTitle;
    }

    /**
     * Analyse l'erreur et prépare l'affichage de l'erreur.
     *
     * @param Throwable $ex L'exception interne levée.
     * @return array
     */
    private function &getDebugMessages(Throwable $ex): array
    {
        $messages = array();
        $this->appendException($ex,
                               $messages);
        $this->appendSqlErrors($messages);
        $this->appendLoggerErrors($messages);
        return $messages;
    }

    /**
     * Ajoute des informations sur l'exception.
     *
     * @param Throwable $ex
     * @param array $messages
     */
    private function appendException(Throwable $ex,
                                     array &$messages): void
    {
        $this->appendExceptionMessage($ex,
                                      $messages);
        $messages[] = '';
        $this->appendExceptionTrace($ex,
                                    $messages);
    }

    /**
     * Ajoute une information générale sur l'exception.
     *
     * @param Throwable $ex
     * @param array $messages
     */
    private function appendExceptionMessage(Throwable $ex,
                                            array &$messages): void
    {
        if ($this->debuggingMode) {
            if ($ex instanceof FailBase) {
                $messages[] = 'Exception ' . $ex->getFailSourceName() . ' (' . $ex->getCode() . ') : ' . $ex->getMessage();
                $messages = array_merge($messages,
                                        $ex->getFailArgs());
            } else {
                $messages[] = 'Exception PHP (' . $ex->getCode() . ') : ' . $ex->getMessage();
            }
        }
    }

    /**
     * Ajoute la trace (pile d'appel) de l'exception.
     *
     * @param Throwable $ex
     * @param array $messages
     */
    private function appendExceptionTrace(Throwable $ex,
                                          array &$messages): void
    {
        foreach ($ex->getTrace() as $traceValue) {
            $errorLine = '';

            if (is_array($traceValue)) {
                foreach ($traceValue as $key => $value) {
                    if ($key === 'file' || $key === 'function') {
                        $value = preg_replace('/([a-zA-Z0-9._]+).php/',
                                              '<span class="text_bold">\\1</span>.php',
                                              $value);
                        $errorLine .= ' <span class="text_bold">' . $key . '</span> ' . $value;
                    } else if ($key === 'args' && !empty($value) && is_array($value)) {
                        $errorLine .= ' with [<span class="text_small">' . print_r($value,
                                                                                   true)
                            . '</span>]';
                    } else if ($key === 'line' || $key == 'class') {
                        $errorLine .= ' in <span class="text_bold">' . $key . '</span> ' . $value;
                    }
                }
            }

            if (!empty($errorLine)) {
                $messages[] = $errorLine;
            }
        }
    }

    /**
     * Ajoute des informations sur les dernières erreurs SQL.
     *
     * @param array $messages
     */
    private function appendSqlErrors(array &$messages): void
    {
        if ($this->debuggingMode && CoreLoader::isCallable('CoreSql')) {
            if (CoreSql::hasConnection()) {
                $sqlErrors = CoreSql::getInstance()->getSelectedBase()->getLastError();

                if (empty($sqlErrors)) {
                    $sqlErrors = '(empty)';
                }

                if (!empty($sqlErrors)) {
                    $messages[] = '';
                    $messages[] = '<span class="text_bold">Last Sql error message:</span>';
                    $messages = array_merge($messages,
                                            $sqlErrors);
                }
            } else {
                $messages[] = 'No connection available to the database.';
            }
        }
    }

    /**
     * Ajoute des informations sur les erreurs collectées dans le journal.
     *
     * @param array $messages
     */
    private function appendLoggerErrors(array &$messages): void
    {
        if (CoreLoader::isCallable('CoreLogger')) {
            $loggerExceptions = CoreLogger::getExceptions();

            if (!empty($loggerExceptions)) {
                $messages[] = '';
                $messages[] = '<span class="text_bold">Exceptions logged:</span>';
                $messages = array_merge($messages,
                                        $loggerExceptions);
            }
        }
    }

    /**
     * Réglages de la sortie d'erreurs.
     * Affichage de toutes les erreurs.
     */
    private function configureOutput(): void
    {
        error_reporting(E_ALL);
    }

    /**
     * Vérification des données reçues (depuis QUERY_STRING).
     *
     * @throws FailEngine
     */
    private function checkServerQueryString(): void
    {
        $query = CoreRequest::getQueryString();

        if (!empty($query)) {
            $queryString = strtolower(rawurldecode($query));

            foreach (self::BAD_QUERY_STRINGS as $badStringValue) {
                if (strpos($queryString,
                           $badStringValue)) {
                    throw new FailEngine('invalid query string',
                                         FailBase::getErrorCodeName(11));
                }
            }
        }
    }

    /**
     * Vérification de la provenance des requêtes.
     *
     * @throws FailEngine
     */
    private function checkServerRequest(): void
    {
        if (CoreRequest::getRequestMethod() === CoreRequestType::POST && !empty(CoreRequest::getHttpReferer())) {
            // Vérification du demandeur de la méthode POST
            if (!preg_match('/' . CoreRequest::getString('HTTP_HOST',
                                                         '',
                                                         CoreRequestType::SERVER) . '/',
                                                         CoreRequest::getHttpReferer())) {
                throw new FailEngine('invalid request/referer',
                                     FailBase::getErrorCodeName(12));
            }
        }
    }

    /**
     * Vérification des variables globales.
     */
    private function checkGlobals(): void
    {
        $this->addSlashesForQuotes(self::getGlobalGet());
        $this->addSlashesForQuotes(self::getGlobalPost());
        $this->addSlashesForQuotes(self::getGlobalCookie());
    }

    /**
     * Ajoute un caractère d'échappement pour chaque caractère de citation.
     *
     * @param mixed $key objet sans antislash
     */
    private function addSlashesForQuotes(&$key): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (is_array($key[$k])) {
                    $this->addSlashesForQuotes($key[$k]);
                } else {
                    $key[$k] = addslashes($v);
                }
            }

            reset($key);
        } else {
            $key = addslashes($key);
        }
    }

    /**
     * Classe autorisée à manipuler $_GET (lecture et écriture).
     *
     * @return array
     */
    private static function &getGlobalGet(): array
    {
        $vars = &CoreInfo::getGlobalVars(CoreRequestType::GET);
        return $vars;
    }

    /**
     * Classe autorisée à manipuler $_POST (lecture et écriture).
     *
     * @return array
     */
    private static function &getGlobalPost(): array
    {
        $vars = &CoreInfo::getGlobalVars(CoreRequestType::POST);
        return $vars;
    }

    /**
     * Classe autorisée à manipuler $_COOKIE (lecture et écriture).
     *
     * @return array
     */
    private static function &getGlobalCookie(): array
    {
        $vars = &CoreInfo::getGlobalVars(CoreRequestType::COOKIE);
        return $vars;
    }
}