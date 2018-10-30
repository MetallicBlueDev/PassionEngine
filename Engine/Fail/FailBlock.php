<?php

namespace PassionEngine\Engine\Fail;

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