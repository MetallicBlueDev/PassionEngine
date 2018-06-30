<?php

namespace TREngine\Engine\Fail;

/**
 * Exception inconnue lancée par le moteur.
 *
 * @author Sébastien Villemain
 */
class FailEngine extends FailBase
{

    /**
     * Nouvelle erreur interne.
     *
     * @param string $message
     * @param int $failCode
     * @param array $failArgs
     */
    public function __construct(string $message, int $failCode = 0, array $failArgs = array())
    {
        parent::__construct($message,
                            $failCode,
                            $failArgs);
    }
}