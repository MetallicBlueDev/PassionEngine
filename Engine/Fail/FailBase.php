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
     * Création d'une nouvelle exception.
     *
     * @param string $message
     * @param int $failSourceNumber
     */
    public function __construct(string $message, int $failSourceNumber = FailFrom::ENGINE) {
        parent::__construct($message, $failSourceNumber, null);
    }

    /**
     * Retourne le nom de la source de l'exception.
     *
     * @return string
     */
    public function getFailSourceName(): string {
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

    /**
     * Retour une description de base sur l'exception.
     *
     * @return string
     */
    public function getFailInformation(): string {
        return "Exception " . $this->getFailSourceName() . " (" . $this->getCode() . ") : " . $this->getMessage();
    }
}