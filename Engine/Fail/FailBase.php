<?php

namespace TREngine\Engine\Fail;

use Exception;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Base d'une exception du moteur.
 *
 * @author Sébastien Villemain
 */
abstract class FailBase extends Exception {

    /**
     * Liste des codes d'erreur.
     * Ne pas changer le numéro d'une erreur.
     *
     *
     * @var array
     */
    private const ERROR_CODES = array(
        0 => "genericErrorCode",
        1 => "accessRank",
        2 => "cacheType",
        3 => "cacheCode",
        4 => "loader",
        5 => "cachePath",
        6 => "sqlPath",
        7 => "configPath",
        8 => "siteClosed",
        9 => "requestHash",
        10 => "badUrl",
        11 => "badQueryString",
        12 => "badRequestReferer",
        13 => "sqlType",
        14 => "sqlCode",
        15 => "blockType",
        16 => "blockSide",
        17 => "makeStyle",
        18 => "makeStyleConfig",
        19 => "sqlReq",
        20 => "netConnect",
        21 => "netSelect",
        22 => "netCanUse",
        9999 => "??"
    );

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
    public function __construct(string $message, int $failCode = 0, array $failArgs = array()) {
        parent::__construct($message, $failCode, null);
        $this->failSourceName = self::makeFailSourceName();
        $this->failArgs = $failArgs;
    }

    /**
     * Retourne le nom de la source de l'exception.
     *
     * @return string
     */
    public function getFailCodeName(): string {
        $codeName = "";

        if (isset(self::ERROR_CODES[$this->getCode()])) {
            $codeName = self::ERROR_CODES[$this->getCode()];
        } else {
            $codeName = self::ERROR_CODES[0];
        }
        return $codeName;
    }

    /**
     * Retourne le nom de la source de l'exception.
     *
     * @return string
     */
    public function getFailSourceName(): string {
        return $this->failSourceName;
    }

    /**
     * Retourne les informations supplémentaires sur l'erreur.
     *
     * @return string
     */
    public function getFailArgs(): array {
        return $this->failArgs;
    }

    /**
     * Retourne la description du code d'erreur (constantes).
     *
     * @param string $shortCodeName
     * @return string
     */
    public static function &getErrorCodeDescription(string $shortCodeName): string {
        $rslt = "";
        $fullCodeName = "ERROR_CODE_" . strtoupper($shortCodeName);

        if (defined($fullCodeName)) {
            $rslt = constant($fullCodeName);
        }
        return $rslt;
    }

    /**
     * Détermine le nom de la source de l'exception.
     *
     * @return string
     */
    private static function makeFailSourceName(): string {
        $sourceName = get_called_class();
        $pos = strripos($sourceName, '\\');

        if ($pos > 1) {
            $sourceName = substr($sourceName, $pos + 1, strlen($sourceName) - $pos - 1);
        }

        $pos = strcspn($sourceName, 'ABCDEFGHJIJKLMNOPQRSTUVWXYZ', 1);

        if ($pos > 1) {
            $sourceName = substr($sourceName, $pos + 1, strlen($sourceName) - $pos - 1);
        }
        return $sourceName;
    }
}