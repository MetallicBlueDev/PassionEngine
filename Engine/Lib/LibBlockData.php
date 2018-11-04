<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Core\CoreMain;
use PassionEngine\Engine\Core\CoreAccessZone;
use PassionEngine\Engine\Core\CoreLoader;
use PassionEngine\Engine\Exec\ExecString;
use PassionEngine\Engine\Exec\ExecUtils;
use PassionEngine\Engine\Core\CoreUrlRewriting;

/**
 * Information de base sur un block.
 *
 * @author Sébastien Villemain
 */
class LibBlockData extends LibEntityData
{

    /**
     * Nom de la position du block.
     *
     * @var string
     */
    private $sideName = '';

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

        $this->updateDataValue('title',
                               ExecString::textDisplay($this->getString('title')));

        // Affecte le nom de la position du block.
        $this->sideName = LibBlock::getSideAsLetters($this->getSide());
    }

    /**
     * Retourne l'identifiant du block.
     *
     * @return int
     */
    public function &getId(): int
    {
        return $this->getInt('block_id',
                             -1);
    }

    /**
     * Retourne le nom de la position du block.
     *
     * @return string
     */
    public function &getName(): string
    {
        return $this->getSideName();
    }

    /**
     * Retourne le rang pour accéder au block.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt('rank');
    }

    /**
     * Détermine si le block correspond au nom demandé.
     *
     * @return bool
     */
    public function hasFolderName(string $name): bool
    {
        return (bool) (!empty($name) && ucfirst($name) === $this->getType());
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
        return CoreLoader::BLOCK_FILE . $this->getPage();
    }

    /**
     * Détermine si le block est installé.
     *
     * @return bool
     */
    public function installed(): bool
    {
        return $this->hasValue('block_id');
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
     * {@inheritDoc}
     */
    public function buildFinalOutput(): void
    {
        LibBlock::getInstance()->buildEntityData($this);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getFinalOutput(): string
    {
        $buffer = $this->getTemporyOutputBuffer();
        $configs = $this->getConfigs();

        // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
        if (ExecUtils::inArrayStrictCaseInSensitive('rewriteBuffer',
                                                    $configs)) {
            $buffer = CoreUrlRewriting::getInstance()->rewriteBuffer($buffer);
        }

        return $buffer;
    }

    /**
     * Détermine si l'entité peut être utilisée.
     *
     * @return bool true L'entité peut être utilisée.
     */
    public function &canUse(): bool
    {
        $rslt = false;

        if ($this->accessAllowed()) {
            if (CoreMain::getInstance()->getRoute()->isBlockLayout()) {
                $rslt = true;
            } else {
                $rslt = $this->canActiveForModule();
            }
        }
        return $rslt;
    }

    /**
     * Retourne l'identifiant de la position du block.
     *
     * @return int
     */
    public function &getSide(): int
    {
        return $this->getInt('side');
    }

    /**
     * Retourne le nom de la position du block.
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
        $templateName = 'block_' . $this->sideName;
        return $templateName;
    }

    /**
     * Retourne le titre du block.
     *
     * @return string
     */
    public function &getTitle(): string
    {
        return $this->getString('title');
    }

    /**
     * Retourne la configuration du block.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getConfigs(): ?array
    {
        return $this->getArray('block_config');
    }

    /**
     * Retourne les modules cibles.
     *
     * @return array
     */
    public function &getAllowedModules(): array
    {
        $rslt = $this->getArray('module_ids',
                                array());
        return $rslt;
    }

    /**
     * Détermine si tous les modules sont ciblés.
     *
     * @return bool
     */
    public function &canDisplayOnAllModules(): bool
    {
        return $this->getBool('all_modules');
    }

    /**
     * Retourne le type de block.
     *
     * @return string
     */
    public function &getType(): string
    {
        $dataValue = ucfirst($this->getString('type'));
        return $dataValue;
    }

    /**
     * Vérifie si le block doit être activé sur le module actuel.
     *
     * @return bool
     */
    private function &canActiveForModule(): bool
    {
        $rslt = false;

        if (CoreLoader::isCallable('LibModule')) {
            if ($this->canDisplayOnAllModules()) {
                $rslt = true;
            } else {
                $selectedModuleId = CoreMain::getInstance()->getRoute()->getRequestedModuleData()->getId();

                foreach ($this->getAllowedModules() as $allowedModule) {
                    if ($selectedModuleId === $allowedModule['module_id']) {
                        $rslt = true;
                        break;
                    }
                }
            }
        }
        return $rslt;
    }
}