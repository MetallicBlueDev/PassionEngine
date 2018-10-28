<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Fail\FailEngine;

/**
 * Lanceur d'exception du moteur.
 *
 * @author Sébastien Villemain
 */
trait CoreTraitException
{

    /**
     * Lance une exception.
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @throws FailEngine
     */
    protected function throwException(string $message,
                                      string $failCode = '',
                                      array $failArgs = array()): void
    {
        throw new FailEngine($message,
                             $failCode,
                             $failArgs);
    }
}