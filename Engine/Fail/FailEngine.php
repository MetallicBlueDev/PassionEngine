<?php

namespace PassionEngine\Engine\Fail;

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
     * @param string $failCode
     * @param array $failArgs
     * @param bool $useArgsInTranslate
     */
    public function __construct(string $message,
                                string $failCode = '',
                                array $failArgs = array(),
                                bool $useArgsInTranslate = false)
    {
        parent::__construct($message,
                            $failCode,
                            $failArgs,
                            $useArgsInTranslate);
    }
}