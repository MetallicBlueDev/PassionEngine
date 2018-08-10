<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par un block.
 *
 * @author Sébastien Villemain
 */
class FailBlock extends FailBase
{

    /**
     * Nouvelle erreur lié à un block.
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