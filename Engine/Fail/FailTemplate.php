<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par le moteur de template.
 *
 * @author Sébastien Villemain
 */
class FailTemplate extends FailBase
{

    /**
     * Nouvelle erreur de template.
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