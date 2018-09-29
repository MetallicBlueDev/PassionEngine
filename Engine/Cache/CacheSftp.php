<?php

namespace TREngine\Engine\Cache;

/**
 * Gestionnaire de fichier via FTP sécurisée.
 *
 * @author Sébastien Villemain
 */
class CacheSftp extends CacheModel
{

    /**
     * {@inheritDoc}
     */
    public function netConnect(): void
    {
        // TODO classe a coder..
    }

    /**
     * {@inheritDoc}
     */
    public function netDeconnect(): void
    {

    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    protected function canUse(): bool
    {
        return false;
    }
}