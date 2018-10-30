<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Core\CoreLoader;
use PassionEngine\Engine\Exec\ExecUtils;
use PassionEngine\Engine\Core\CoreAccessZone;
use PassionEngine\Engine\Core\CoreUrlRewriting;

/**
 * Information de base sur un module.
 *
 * @author Sébastien Villemain
 */
class LibModuleData extends LibEntityData
{

    /**
     * Nouvelle information de module.
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
    }

    /**
     * Retourne le nom du module.
     *
     * @return string Le nom du module.
     */
    public function &getName(): string
    {
        $dataValue = ucfirst($this->getString('name'));
        return $dataValue;
    }

    /**
     * Retourne le nom du dossier contenant le module.
     *
     * @return string
     */
    public function getFolderName(): string
    {
        return CoreLoader::MODULE_FILE . $this->getName();
    }

    /**
     * Retourne le nom de classe représentant le module.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return CoreLoader::MODULE_FILE . $this->getPage();
    }

    /**
     * Retourne l'identifiant du module.
     *
     * @return int
     */
    public function &getId(): int
    {
        return $this->getInt('module_id',
                             -1);
    }

    /**
     * Détermine si le module est installé.
     *
     * @return bool
     */
    public function installed(): bool
    {
        return $this->hasValue('module_id');
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getZone(): string
    {
        $zone = CoreAccessZone::MODULE;
        return $zone;
    }

    /**
     * {@inheritDoc}
     */
    public function buildFinalOutput(): void
    {
        LibModule::getInstance()->buildEntityData($this);
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
        if ($configs !== null && ExecUtils::inArrayStrictCaseInSensitive('rewriteBuffer',
                                                                         $configs)) {
            $buffer = CoreUrlRewriting::getInstance()->rewriteBuffer($buffer);
        }
        return $buffer;
    }

    /**
     * Retourne le rang du module.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt('rank');
    }

    /**
     * Détermine si l'entité peut être utilisée.
     *
     * @return bool true L'entité peut être utilisée.
     */
    public function &canUse(): bool
    {
        $rslt = $this->accessAllowed();
        return $rslt;
    }

    /**
     * Retourne la configuration du module.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getConfigs(): ?array
    {
        return $this->getArray('module_config');
    }

    /**
     * Retourne la valeur de la clé de configuration du module.
     *
     * @param string $key
     * @param string $defaultValue
     * @return string
     */
    public function &getConfigValue(string $key,
                                    string $defaultValue = ''): string
    {
        return $this->getSubString('module_config',
                                   $key,
                                   $defaultValue);
    }
}