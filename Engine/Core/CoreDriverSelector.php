<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Fail\FailBase;

/**
 * Modèle de base pour une sélection d'un pilote à utiliser.
 *
 * @author Sébastien Villemain
 */
abstract class CoreDriverSelector
{

    use CoreTraitException;

    /**
     * Modèle de transaction sélectionnée.
     *
     * @var CoreTransaction
     */
    private $selectedDriver = null;

    /**
     * Destruction du gestionnaire.
     */
    public function __destruct()
    {
        unset($this->selectedDriver);
    }

    /**
     * Création de l'instance du pilote à utiliser.
     *
     * @throws FailBase
     */
    protected function createDriver(): void
    {
        $rawConfigs = $this->getConfiguration();
        $fullClassName = $this->getFullClassName($rawConfigs);
        $this->selectedDriver = $this->makeSelectedBase($fullClassName,
                                                        $rawConfigs);
        $this->onInitialized();
    }

    /**
     * Retourne un tableau contenant la configuration.
     *
     * @return array
     */
    abstract protected function getConfiguration(): array;

    /**
     * Retourne le préfixe du fichier pilote.
     *
     * @return string
     */
    abstract protected function getFilePrefix(): string;

    /**
     * Routine à exécuter après l'initialisation du pilote.
     */
    protected function onInitialized(): void
    {

    }

    /**
     * Retourne l'instance du pilote.
     *
     * @return CoreTransaction
     */
    protected function &getSelectedDriver(): CoreTransaction
    {
        return $this->selectedDriver;
    }

    /**
     * Retourne le nom complet de la classe représentant le pilote à utiliser.
     *
     * @param array $rawConfigs
     * @return string
     * @throws FailBase
     */
    private function &getFullClassName(array &$rawConfigs): string
    {
        $fullClassName = '';
        $loaded = false;

        if (!empty($rawConfigs) && isset($rawConfigs['type'])) {
            $fullClassName = CoreLoader::getFullQualifiedClassName($this->getFilePrefix() . ucfirst($rawConfigs['type']));
            $loaded = CoreLoader::classLoader($fullClassName);
        }

        if (!$loaded) {
            $this->throwException('driver not found',
                                  FailBase::getErrorCodeName(2),
                                                             array($rawConfigs['type']),
                                                             true);
        }

        if (!CoreLoader::isCallable($fullClassName,
                                    'initialize')) {
            $this->throwException('unable to initialize driver',
                                  FailBase::getErrorCodeName(3),
                                                             array($fullClassName),
                                                             true);
        }
        return $fullClassName;
    }

    /**
     * Création de l'instance du pilote à utiliser.
     *
     * @param string $fullClassName
     * @param array $rawConfigs
     * @return CoreTransaction
     * @throws FailBase
     */
    private function &makeSelectedBase(string $fullClassName,
                                       array &$rawConfigs): CoreTransaction
    {
        /**
         * @var CoreTransaction
         */
        $selectedBase = new $fullClassName();
        $selectedBase->initialize($rawConfigs);
        return $selectedBase;
    }
}