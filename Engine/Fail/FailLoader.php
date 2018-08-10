<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par le chargeur de classe.
 *
 * @author Sébastien Villemain
 */
class FailLoader extends FailBase
{

    /**
     * Nouvelle erreur de chargement de classe.
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