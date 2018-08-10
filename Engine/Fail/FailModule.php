<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par un mobule.
 *
 * @author Sébastien Villemain
 */
class FailModule extends FailBase
{

    /**
     * Nouvelle erreur lié à un module.
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     */
    public function __construct(string $message, string $failCode = "", array $failArgs = array())
    {
        parent::__construct($message,
                            $failCode,
                            $failArgs);
    }
}