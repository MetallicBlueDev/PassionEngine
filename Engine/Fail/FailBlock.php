<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

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