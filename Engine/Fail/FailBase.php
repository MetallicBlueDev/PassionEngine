<?php

namespace PassionEngine\Engine\Fail;

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
    private const ERROR_CODE = 'ERROR_CODE_';

    /**
     * Nom de la source de l'exception.
     *
     * @var string
     */
    private $failSourceName = '';

    /**
     * Informations supplémentaires sur l'erreur.
     *
     * @var array
     */
    private $failArgs = array();

    /**
     * Utiliser le tableau de valeur dans la traduction du message.
     *
     * @var bool
     */
    private $useArgsInTranslate = false;

    /**
     * Création d'une nouvelle exception.
     *
     * @param string $message Message brut.
     * @param int $failCode Code d'erreur.
     * @param array $failArgs Tableau de valeur.
     * @param bool $useArgsInTranslate Détermine si il faut utiliser le tableau de valeur dans la traduction du message.
     */
    public function __construct(string $message,
                                string $failCode = '',
                                array $failArgs = array(),
                                bool $useArgsInTranslate = false)
    {
        parent::__construct($message,
                            self::getErrorCodeValue($failCode),
                                                    null);
        $this->failSourceName = self::makeFailSourceName();
        $this->failArgs = $failArgs;
        $this->useArgsInTranslate = $useArgsInTranslate;
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
     * Détermine si il faut utiliser le tableau de valeur dans la traduction du message.
     *
     * @return bool
     */
    public function useArgsInTranslate(): bool
    {
        return $this->useArgsInTranslate;
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
     * Retourne la valeur du code d'erreur.
     *
     * @param string $errorCodeName Nom du code d'erreur.
     * @return int Valeur du code d'erreur.
     */
    public static function &getErrorCodeValue(string $errorCodeName): int
    {
        $rslt = 0;

        if (!empty($errorCodeName)) {
            $codeValue = str_replace(self::ERROR_CODE,
                                     '',
                                     $errorCodeName);

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