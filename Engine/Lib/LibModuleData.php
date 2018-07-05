<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreAccessZone;

/**
 * Information de base sur un module.
 *
 * @author Sébastien Villemain
 */
class LibModuleData extends LibEntityData
{

    /**
     * La page sélectionnée.
     *
     * @var string
     */
    private $page = null;

    /**
     * La sous page.
     *
     * @var string
     */
    private $view = null;

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
            $rawConfigs = isset($data['configs']) ? $data['configs'] : array();
            $data['configs'] = self::getModuleConfigs($rawConfigs);
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
    public function &getId(): string
    {
        return $this->getIdAsInt();
    }

    /**
     * Détermine si le module est installé.
     *
     * @return bool
     */
    public function installed(): bool
    {
        return $this->hasValue("mod_id");
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
     * Retourne le rang du module.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt("rank");
    }

    /**
     * Retourne la page sélectionnée.
     *
     * @return string
     */
    public function &getPage(): string
    {
        return $this->page;
    }

    /**
     * Affecte la page sélectionnée.
     *
     * @param string $page
     */
    public function setPage(string $page)
    {
        $this->page = ucfirst($page);
    }

    /**
     * Retourne la sous-page sélectionnée.
     *
     * @return string
     */
    public function &getView(): string
    {
        return $this->view;
    }

    /**
     * Affecte la sous-page sélectionnée.
     *
     * @param string $view
     */
    public function setView(string $view)
    {
        $this->view = $view;
    }

    /**
     * Retourne l'identifiant du module.
     *
     * @return int
     */
    public function &getIdAsInt(): int
    {
        return $this->getInt("mod_id");
    }

    /**
     * Retourne la configuration du module.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getConfigs(): array
    {
        return $this->getArray("configs");
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
        return $this->getSubString("configs",
                                   $key,
                                   $defaultValue);
    }

    /**
     * Retourne le jeu de configuration du module.
     *
     * @param string $moduleConfigs
     * @return array
     */
    private static function getModuleConfigs(string &$moduleConfigs): array
    {
        $moduleConfigs = explode("|",
                                 $moduleConfigs);

        foreach ($moduleConfigs as $config) {
            if (!empty($config)) {
                $values = explode("=",
                                  $config);

                if (count($values) > 1) {
                    // Chaine encodé avec urlencode
                    $moduleConfigs[$values[0]] = urldecode($values[1]);
                }
            }
        }

        if ($moduleConfigs === null) {
            $moduleConfigs = array();
        }
        return $moduleConfigs;
    }
}