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

        if (isset(FailFrom::ERROR_CODES[$this->getCode()])) {
            $codeName = FailFrom::ERROR_CODES[$this->getCode()];
        } else {
            $codeName = FailFrom::ERROR_CODES[0];
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