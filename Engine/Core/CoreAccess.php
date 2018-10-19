<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Fail\FailEngine;
use TREngine\Engine\Fail\FailBase;

/**
 * Gestionnaire des accès et autorisation.
 *
 * @author Sébastien Villemain
 */
class CoreAccess
{

    /**
     * Retourne l'erreur d'accès liée au jeton.
     *
     * @param CoreAccessToken $token
     * @return string
     */
    public static function &getAccessErrorMessage(?CoreAccessToken $token): string
    {
        $error = ERROR_ACCES_FORBIDDEN;

        if ($token !== null) {
            if ($token->getRank() === -1) {
                $error = ERROR_ACCES_OFF;
            } else {
                $sessionData = CoreSession::getInstance()->getSessionData();

                if ($token->getRank() === 1 && !$sessionData->hasRegisteredRank()) {
                    $error = ERROR_ACCES_MEMBER;
                } else if ($token->getRank() > 1 && $sessionData->getRank() < $token->getRank()) {
                    $error = ERROR_ACCES_ADMIN;
                }
            }
        }
        return $error;
    }

    /**
     * Autorise ou refuse l'accès à la ressource cible.
     *
     * @param CoreAccessType $accessType
     * @param bool $forceSpecificRank
     * @return bool
     */
    public static function &autorize(CoreAccessType &$accessType,
                                     bool $forceSpecificRank = false): bool
    {
        $rslt = false;

        if ($accessType->valid()) {
            $sessionData = CoreSession::getInstance()->getSessionData();

            if ($sessionData->getRank() >= $accessType->getRank()) {
                if ($accessType->getRank() === CoreAccessRank::SPECIFIC_RIGHT || $forceSpecificRank) {
                    $rslt = self::autorizeSpecific($accessType,
                                                   $sessionData);
                } else {
                    $rslt = true;
                }
            }
        }
        return $rslt;
    }

    /**
     * Retourne le type d'accès avec la traduction.
     *
     * @param int $rank
     * @return string accès traduit (si possible).
     */
    public static function &getRankAsLitteral(int $rank): string
    {
        $rankLitteral = array_search($rank,
                                     CoreAccessRank::RANK_LIST);

        if ($rankLitteral === false) {
            throw new FailEngine('invalid rank number',
                                 FailBase::getErrorCodeName(1),
                                                            array($rank),
                                                            true);
        }

        $rankLitteral = defined($rankLitteral) ? constant($rankLitteral) : $rankLitteral;
        return $rankLitteral;
    }

    /**
     * Liste des niveaux d'accès disponibles.
     *
     * @return array array('numeric' => identifiant entier, 'letters' => nom du niveau)
     */
    public static function &getRankList(): array
    {
        $rankList = array();

        foreach (CoreAccessRank::RANK_LIST as $rank) {
            $rankList[] = array(
                'numeric' => $rank,
                'letters' => self::getRankAsLitteral($rank));
        }
        return $rankList;
    }

    /**
     * Autorise ou refuse l'accès à la ressource cible.
     *
     * @param CoreAccessType $accessType
     * @param CoreSessionData $sessionData
     * @return bool
     */
    private static function &autorizeSpecific(CoreAccessType &$accessType,
                                              CoreSessionData $sessionData): bool
    {
        $rslt = false;

        foreach ($sessionData->getRights() as $userAccessType) {
            if (!$userAccessType->valid()) {
                continue;
            }

            if ($accessType->isAssignableFrom($userAccessType)) {
                $rslt = true;
                break;
            }
        }
        return $rslt;
    }
}