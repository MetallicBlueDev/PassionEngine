<?php

namespace PassionEngine\Engine\Fail;

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