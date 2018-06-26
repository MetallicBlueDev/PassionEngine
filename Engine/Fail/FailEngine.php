<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception inconnue lancée par le moteur.
 *
 * @author Sébastien Villemain
 */
class FailEngine extends FailBase {

    /**
     * Exception déclenchée par une erreur interne.
     *
     * @param string $message
     * @param int $failCode
     * @param array $failArgs
     */
    public function __construct(string $message, int $failCode = 0, array $failArgs = array()) {
        parent::__construct($message, $failCode, $failArgs);
    }
}