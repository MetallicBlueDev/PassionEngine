<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Exec\ExecUtils;
use TREngine\Engine\Core\CoreAccessZone;
use TREngine\Engine\Core\CoreUrlRewriting;

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
     * @param bool $initializeConfig
     */
    public function __construct(array &$data,
                                bool $initializeConfig = false)
    {
        parent::__construct();

        // Vérification des informations
        if (count($data) < 3) {
            $data = array();
        }

        if ($initializeConfig) {
            $data['module_config'] = isset($data['module_config']) ? ExecUtils::getArrayConfigs($data['module_config']) : array();
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
        $dataValue = ucfirst($this->getString("name"));
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
        return $this->getInt("module_id", -1);
    }

    /**
     * Détermine si le module est installé.
     *
     * @return bool
     */
    public function installed(): bool
    {
        return $this->hasValue("module_id");
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
        LibModule::getInstance()->buildModuleData($this);
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
        if ($configs !== null && ExecUtils::inArray("rewriteBuffer",
                                                    $configs,
                                                    false)) {
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
        return $this->getInt("rank");
    }

    /**
     * Retourne la configuration du module.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getConfigs(): ?array
    {
        return $this->getArray("module_config");
    }

    /**
     * Retourne la valeur de la clé de configuration du module.
     *
     * @param string $key
     * @param string $defaultValue
     * @return string
     */
    public function &getConfigValue(string $key,
                                    string $defaultValue = ""): string
    {
        return $this->getSubString("module_config",
                                   $key,
                                   $defaultValue);
    }
}