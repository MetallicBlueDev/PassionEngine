<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par le cache.
 *
 * @author Sébastien Villemain
 */
class FailCache extends FailBase
{

    /**
     * Nouvelle erreur de cache.
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