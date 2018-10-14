<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par un module.
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
     * @param bool $useArgsInTranslate
     */
    public function __construct(string $message,
                                string $failCode = "",
                                array $failArgs = array(),
                                bool $useArgsInTranslate = false)
    {
        parent::__construct($message,
                            $failCode,
                            $failArgs,
                            $useArgsInTranslate);
    }
}