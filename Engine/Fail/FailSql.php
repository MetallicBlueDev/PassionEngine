<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par le module SQL.
 *
 * @author Sébastien Villemain
 */
class FailSql extends FailBase
{

    /**
     * Nouvelle erreur SQL.
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