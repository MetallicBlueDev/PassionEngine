<?php

namespace TREngine\Engine\Fail;

use Exception;

/**
 * Base d'une exception du moteur.
 *
 * @author Sébastien Villemain
 */
abstract class FailBase extends Exception
{

    /**
     * Code d'erreur.
     *
     * @var string
     */
    private const ERROR_CODE = "ERROR_CODE_";

    /**
     * Nom de la source de l'exception.
     *
     * @var string
     */
    private $failSourceName = "";

    /**
     * Informations supplémentaires sur l'erreur.
     *
     * @var array
     */
    private $failArgs = array();

    /**
     * Création d'une nouvelle exception.
     *
     * @param string $message
     * @param int $failCode
     * @param array $failArgs
     */
    public function __construct(string $message, string $failCode = "", array $failArgs = array())
    {
        parent::__construct($message,
                            self::getErrorCodeValue($failCode),
                                                    null);
        $this->failSourceName = self::makeFailSourceName();
        $this->failArgs = $failArgs;
    }

    /**
     * Retourne le nom de la source de l'exception.
     *
     * @return string
     */
    public function getFailSourceName(): string
    {
        return $this->failSourceName;
    }

    /**
     * Retourne les informations supplémentaires sur l'erreur.
     *
     * @return string
     */
    public function getFailArgs(): array
    {
        return $this->failArgs;
    }

    /**
     * Retourne le nom du code d'erreur.
     *
     * @param int $codeValue Valeur du code d'erreur.
     * @return string Nom du code d'erreur.
     */
    public static function &getErrorCodeName(int $codeValue): string
    {
        $errorCodeName = self::ERROR_CODE . $codeValue;
        return $errorCodeName;
    }

    /**
     * Retourne la description du code d'erreur (une constante).
     *
     * @param string $errorCodeName Nom du code d'erreur.
     * @return string Description du code d'erreur.
     */
    public static function &getErrorCodeDescription(string $errorCodeName): string
    {
        $rslt = "";

        if (!empty($errorCodeName) && defined($errorCodeName)) {
            $rslt = constant($errorCodeName);
        }
        return $rslt;
    }

    /**
     * Retourne la valeur du code d'erreur.
     *
     * @param string $errorCodeName Nom du code d'erreur.
     * @return int Valeur du code d'erreur.
     */
    public static function &getErrorCodeValue(string $errorCodeName): int
    {
        $rslt = 0;

        if (!empty($errorCodeName)) {
            $codeValue = str_replace(self::ERROR_CODE, "", $errorCodeName);

            if (is_numeric($codeValue)) {
                $rslt = (int) $codeValue;
            }
        }

        return $rslt;
    }

    /**
     * Détermine le nom de la source de l'exception.
     *
     * @return string
     */
    private static function makeFailSourceName(): string
    {
        $sourceName = get_called_class();
        $pos = strripos($sourceName,
                        '\\');

        if ($pos > 1) {
            $sourceName = substr($sourceName,
                                 $pos + 1,
                                 strlen($sourceName) - $pos - 1);
        }

        $pos = strcspn($sourceName,
                       'ABCDEFGHJIJKLMNOPQRSTUVWXYZ',
                       1);

        if ($pos > 1) {
            $sourceName = substr($sourceName,
                                 $pos + 1,
                                 strlen($sourceName) - $pos - 1);
        }
        return $sourceName;
    }
}