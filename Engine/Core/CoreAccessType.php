<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibBlock;
use TREngine\Engine\Lib\LibModule;

/**
 * Représente un type d'accès à une zone spécifique.
 *
 * @author Sébastien Villemain
 */
class CoreAccessType extends CoreDataStorage implements CoreAccessToken
{

    /**
     * Accès passe partout.
     *
     * @var string
     */
    private const FULL_ACCESS = "all";

    /**
     * Identifiant de l'accès passe partout.
     *
     * @var int
     */
    private const FULL_ACCESS_ID = -555;

    /**
     * Cache pour l'accès passe partout.
     *
     * @var CoreAccessType
     */
    private static $fullAccessType = null;

    /**
     * Nouveau type d'accès à vérifier.
     *
     * @param array $rights
     */
    protected function __construct(array &$rights)
    {
        parent::__construct();
        $this->newStorage($rights);
    }

    /**
     * Retourne l'accès spécifique suivant la zone.
     *
     * @param CoreAccessToken $token
     * @return CoreAccessType
     */
    public static function &getTypeFromToken(CoreAccessToken $token): CoreAccessType
    {
        return self::getTypeFromDatas($token->getZone(),
                                      $token->getRank(),
                                      $token->getId(),
                                      $token->getName());
    }

    /**
     * Retourne l'accès typé administrateur.
     *
     * @return CoreAccessType
     */
    public static function &getTypeFromAdmin(): CoreAccessType
    {
        if (self::$fullAccessType === null) {
            self::$fullAccessType = self::getTypeFromDatas(self::FULL_ACCESS,
                                                           CoreAccessRank::ADMIN,
                                                           self::FULL_ACCESS_ID,
                                                           "admin");
        }
        return self::$fullAccessType;
    }

    /**
     * Retourne l'accès spécifique suivant les données précisées.
     *
     * @param string $zone
     * @param int $rank
     * @param int $identifier
     * @param string $name
     * @return CoreAccessType
     */
    public static function &getTypeFromDatas(string $zone,
                                             int $rank,
                                             int $identifier,
                                             string $name): CoreAccessType
    {
        $infos = array(
            "zone" => $zone,
            "rank" => $rank,
            "identifier" => $identifier,
            "name" => $name);
        return self::getTypeFromArray($infos);
    }

    /**
     * Retourne le type de compte requit pour cet accès.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt("rank",
                             CoreAccessRank::NONE);
    }

    /**
     * Nom du type d'accès (par exemple BLOCK/MODULE).
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getZone(): string
    {
        return $this->getString("zone");
    }

    /**
     * Formule de la page (exemple le nom d'un module).
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getPage(): string
    {
        return $this->getString("page");
    }

    /**
     * Change la formule de la page.
     *
     * @param string $newPage
     */
    public function setPage(string $newPage)
    {
        $this->setDataValue("page",
                            $newPage);
        $this->unsetValue("validity");
    }

    /**
     * Identifiant du type d'accès.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return int
     */
    public function &getId(): int
    {
        return $this->getInt("identifier");
    }

    /**
     * Nom du type d'accès (par exemple home pour un module).
     *
     * @return string
     */
    public function &getName(): string
    {
        return $this->getString("name");
    }

    /**
     * Détermine si c'est un accès à un module.
     *
     * @return bool
     */
    public function isModuleZone(): bool
    {
        return ($this->getZone() === CoreAccessZone::MODULE);
    }

    /**
     * Détermine si c'est un accès à un block.
     *
     * @return bool
     */
    public function isBlockZone(): bool
    {
        return ($this->getZone() === CoreAccessZone::BLOCK);
    }

    /**
     * Détermine si c'est un accès personnalisé.
     *
     * @return bool
     */
    public function isCustomZone(): bool
    {
        return !empty($this->getZone()) && !$this->isBlockZone() && !$this->isModuleZone();
    }

    /**
     * Détermine si c'est un accès à une page spécifique.
     *
     * @return bool
     */
    public function hasPageAccess(): bool
    {
        return !empty($this->getPage());
    }

    /**
     * Détermine si le type d'accès est valide.
     *
     * @return bool
     */
    public function &valid(): bool
    {
        if (!$this->alreadyChecked()) {
            $this->checkValidity();
        }
        return $this->getBool("validity");
    }

    /**
     * Détermine si les accès sont semblables.
     *
     * @param CoreAccessType $otherAccessType
     * @return bool
     */
    public function isAssignableFrom(CoreAccessType $otherAccessType): bool
    {
        $rslt = false;

        if ($otherAccessType->getZone() === $this->getZone() || $otherAccessType->getZone() === self::FULL_ACCESS) {
            if ($otherAccessType->getId() === $this->getId() || $otherAccessType->getId() === self::FULL_ACCESS_ID) {
                if (empty($this->getPage()) || ($this->getPage() === $otherAccessType->getPage() || $otherAccessType->getPage() === self::FULL_ACCESS)) {
                    $rslt = true;
                }
            }
        }
        return $rslt;
    }

    /**
     * Retourne l'accès depuis la table d'information.
     *
     * @param array $rights
     * @return CoreAccessType
     */
    private static function &getTypeFromArray(array &$rights): CoreAccessType
    {
        $newAccess = new CoreAccessType($rights);
        return $newAccess;
    }

    /**
     * Routine de vérification du type d'accès.
     */
    private function checkValidity(): void
    {
        $valid = false;

        if (!empty($this->getZone()) && !empty($this->getId())) {
            if ($this->isModuleZone()) {
                $valid = $this->canOpenModule();
            } else if ($this->isBlockZone()) {
                $valid = $this->canOpenBlock();
            } else {
                $valid = true;
            }
        }

        $this->setDataValue("validity",
                            $valid);
    }

    /**
     * Détermine si il est possible d'ouvrir le module.
     *
     * @return bool
     */
    private function &canOpenModule(): bool
    {
        $valid = false;
        $moduleData = null;

        // Recherche d'informations sur le module
        if (CoreLoader::isCallable("LibModule")) {
            $moduleData = LibModule::getInstance()->getModuleData($this->getName());
        }

        if ($moduleData !== null && $moduleData->getId() >= 0 && $moduleData->isCallableViewMethod()) {
            $this->setDataValue("page",
                                $moduleData->getName());
            $this->setDataValue("identifier",
                                $moduleData->getId());
            $valid = true;
        }
        return $valid;
    }

    /**
     * Détermine si il est possible d'ouvrir le block.
     *
     * @return bool
     */
    private function &canOpenBlock(): bool
    {
        $valid = false;
        $blockInfo = null;

        // Recherche d'information sur le block
        if (CoreLoader::isCallable("LibBlock")) {
            $blockInfo = LibBlock::getInstance()->getBlockData($this->getId());
        }

        if ($blockInfo !== null && $blockInfo->getId() >= 0 && $blockInfo->isCallableViewMethod()) {
            $this->setDataValue("page",
                                $blockInfo->getType());
            $this->setDataValue("identifier",
                                $blockInfo->getId());
            $valid = true;
        }
        return $valid;
    }

    /**
     * Détermine si l'accès a été vérifié.
     *
     * @return bool
     */
    private function alreadyChecked(): bool
    {
        return $this->exist("validity");
    }
}