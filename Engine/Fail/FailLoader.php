<?php

namespace PassionEngine\Engine\Fail;

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