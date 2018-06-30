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