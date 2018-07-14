<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreAccessZone;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Exec\ExecString;

/**
 * Information de base sur un block.
 *
 * @author Sébastien Villemain
 */
class LibBlockData extends LibEntityData
{

    /**
     * Position du block en lettre.
     *
     * @var string
     */
    private $sideName = "";

    /**
     * Nouvelle information de block.
     *
     * @param array $data
     */
    public function __construct(array &$data)
    {
        parent::__construct();

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        $this->newStorage($data);

        $this->updateDataValue("title",
                               ExecString::textDisplay($this->getString("title")));

        // Affecte la position du block en lettre.
        $this->sideName = LibBlock::getSideAsLetters($this->getSide());
    }

    /**
     * Retourne l'identifiant du block.
     *
     * @return string
     */
    public function &getId(): string
    {
        return $this->getIdAsInt();
    }

    /**
     * Retourne la position du block en lettre.
     *
     * @return string
     */
    public function &getName(): string
    {
        return $this->getSideName();
    }

    /**
     * Retourne le rang pour accèder au block.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt("rank");
    }

    /**
     * Retourne le nom du dossier contenant le block.
     *
     * @return string
     */
    public function getFolderName(): string
    {
        return CoreLoader::BLOCK_FILE . $this->getType();
    }

    /**
     * Retourne le nom de classe représentant le block.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return CoreLoader::BLOCK_FILE . $this->getType();
    }

    /**
     * Détermine si le block est installé.
     *
     * @return bool
     */
    public function installed(): bool
    {
        return $this->hasValue("block_id");
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getZone(): string
    {
        $zone = CoreAccessZone::BLOCK;
        return $zone;
    }

    /**
     * Retourne l'identifiant du block.
     *
     * @return int
     */
    public function &getIdAsInt(): int
    {
        return $this->getInt("block_id");
    }

    /**
     * Retourne la position du block en chiffre.
     *
     * @return int
     */
    public function &getSide(): int
    {
        return $this->getInt("side");
    }

    /**
     * Retourne la position du block en lettre.
     *
     * @return string
     */
    public function &getSideName(): string
    {
        return $this->sideName;
    }

    /**
     * Retourne le nom complet du template de block à utiliser.
     *
     * @return string
     */
    public function &getTemplateName(): string
    {
        $templateName = "block_" . $this->sideName;
        return $templateName;
    }

    /**
     * Retourne le titre du block.
     *
     * @return string
     */
    public function &getTitle(): string
    {
        return $this->getString("title");
    }

    /**
     * Retourne le contenu du block.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return string
     */
    public function &getContent(): string
    {
        return $this->getString("content");
    }

    /**
     * Affecte le contenu du block.
     *
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->setDataValue("content",
                            $content);
    }

    /**
     * Retourne les modules cibles.
     *
     * @return array
     */
    public function &getTargetModuleIds(): array
    {
        $rslt = $this->getArray("modIds",
                                array());
        return $rslt;
    }

    /**
     * Détermine si tous les modules sont ciblés.
     *
     * @return bool
     */
    public function &isAllTargetedModules(): bool
    {
        return $this->getBool("allMods");
    }

    /**
     * Retourne le type de block.
     *
     * @return string
     */
    public function &getType(): string
    {
        $dataValue = ucfirst($this->getString("type"));
        return $dataValue;
    }

    /**
     * Vérifie si le block doit être activé.
     *
     * @param bool $checkModule
     * @return bool true le block doit être actif.
     */
    public function &canActive(bool $checkModule = true): bool
    {
        $rslt = false;

        if (CoreAccess::autorize(CoreAccessType::getTypeFromToken($this))) {
            if ($checkModule) {
                $rslt = $this->canActiveForModule();
            } else {
                $rslt = true;
            }
        }
        return $rslt;
    }

    /**
     * Vérifie si le block doit être activé sur le module actuel.
     *
     * @return bool
     */
    private function &canActiveForModule(): bool
    {
        $rslt = false;

        if (CoreLoader::isCallable("LibModule")) {
            if ($this->isAllTargetedModules()) {
                $rslt = true;
            } else {
                $selectedModuleId = LibModule::getInstance()->getRequestedModuleData()->getIdAsInt();

                foreach ($this->getTargetModuleIds() as $allowedModuleId) {
                    if ($selectedModuleId === $allowedModuleId) {
                        $rslt = true;
                        break;
                    }
                }
            }
        }
        return $rslt;
    }
}