<?php

namespace PassionEngine\Engine\Core;

/**
 * Représente un jeton d'accès à une zone.
 *
 * @author Sébastien Villemain
 */
interface CoreAccessTokenInterface
{

    /**
     * Retourne la zone d'échange actuelle.
     *
     * @return string
     */
    public function &getZone(): string;

    /**
     * Retourne l'identifiant de la zone d'échange.
     *
     * @return int
     */
    public function &getId(): int;

    /**
     * Retourne le nom spécifique de la zone d'échange.
     *
     * @return string
     */
    public function &getName(): string;

    /**
     * Retourne le rang de la zone d'échange.
     *
     * @return int
     */
    public function &getRank(): int;
}