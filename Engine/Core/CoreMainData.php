<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Exec\ExecEmail;
use PassionEngine\Engine\Exec\ExecString;
use Closure;

/**
 * Collecteur d'information sur la configuration du moteur.
 *
 * @author Sébastien Villemain
 */
class CoreMainData extends CoreDataStorage
{

    /**
     * Nouvelle information de configuration.
     */
    public function __construct()
    {
        parent::__construct();

        $data = array();
        $this->newStorage($data);
    }

    /**
     * Initialisation de la configuration principale.
     *
     * @return bool
     */
    public function initialize(): bool
    {
        $canUse = false;

        // Tentative d'utilisation de la configuration
        $rawConfig = $this->getArray('Includes_config');

        if (!empty($rawConfig)) {
            $this->loadSpecificConfig($rawConfig);
            $canUse = true;
        }

        // Nettoyage des clés temporaires
        $this->unsetValue('Includes_config');
        return $canUse;
    }

    /**
     * Ajoute les données à la configuration.
     *
     * @param array
     */
    public function addConfig(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $this->setDataValue($key,
                                    $value);
            } else {
                $this->setDataValue($key,
                                    ExecString::stripSlashes($value));
            }
        }
    }

    /**
     * Ajoute les données d'inclusion à la configuration.
     *
     * @param string $name
     * @param array $include
     */
    public function addInclude(string $name,
                               array $include): void
    {
        $this->addConfig(array(
            $name => $include));
    }

    /**
     * Retourne la configuration du cache.
     *
     * @return array
     */
    public function &getConfigCache(): array
    {
        return $this->getArray('Includes_cache');
    }

    /**
     * Retourne la configuration de la base de données.
     *
     * @return array
     */
    public function &getConfigDatabase(): array
    {
        return $this->getArray('Includes_database');
    }

    /**
     * Détermine si la transformation des URL est activé.
     *
     * @return bool
     */
    public function &doUrlRewriting(): bool
    {
        return $this->getBool('urlRewriting');
    }

    /**
     * Vérifie l'état de maintenance.
     *
     * @return bool
     */
    public function isInMaintenanceMode(): bool
    {
        return (!$this->isOpen() && !CoreSession::getInstance()->getSessionData()->hasAdminRank());
    }

    /**
     * Vérifie l'état du site (ouvert/fermé).
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return ($this->getDefaultSiteStatut() === 'open');
    }

    /**
     * Détermine l'état des inscriptions au site.
     *
     * @return bool
     */
    public function &registrationAllowed(): bool
    {
        return $this->getBool('registrationAllowed');
    }

    /**
     * Retourne le préfixe des cookies.
     *
     * @return string
     */
    public function &getCookiePrefix(): string
    {
        return $this->getString('cookiePrefix');
    }

    /**
     * Retourne la durée de validité du cache des sessions.
     *
     * @return int
     */
    public function &getSessionTimeLimit(): int
    {
        return $this->getInt('sessionTimeLimit');
    }

    /**
     * Retourne la clé de chiffrement.
     *
     * @return string
     */
    public function &getCryptKey(): string
    {
        return $this->getString('cryptKey');
    }

    /**
     * Retourne le mode du captcha.
     *
     * @return string
     */
    public function &getCaptchaMode(): string
    {
        return $this->getString('captchaMode');
    }

    /**
     * Retourne l'adresse mail de l'administrateur.
     *
     * @return string
     */
    public function &getDefaultAdministratorEmail(): string
    {
        return $this->getStringValueWithDefault('defaultAdministratorEmail',
                                                function() {
                return PASSION_ENGINE_EMAIL;
            });
    }

    /**
     * Retourne le nom du site.
     *
     * @return string
     */
    public function &getDefaultSiteName(): string
    {
        return $this->getStringValueWithDefault('defaultSiteName',
                                                function() {
                return CoreRequest::getString('SERVER_NAME',
                                              '',
                                              CoreRequestType::SERVER);
            });
    }

    /**
     * Retourne le slogan du site.
     *
     * @return string
     */
    public function &getDefaultSiteSlogan(): string
    {
        return $this->getStringValueWithDefault('defaultSiteSlogan',
                                                function() {
                return 'PassionEngine';
            });
    }

    /**
     * Retourne le statut du site.
     *
     * @return string
     */
    public function &getDefaultSiteStatut(): string
    {
        return $this->getStringValueWithDefault('defaultSiteStatut',
                                                function() {
                return 'open';
            });
    }

    /**
     * Retourne la raison de la fermeture du site.
     *
     * @return string
     */
    public function &getDefaultSiteCloseReason(): string
    {
        return $this->getStringValueWithDefault('defaultSiteCloseReason',
                                                function() {
                return 'Site is closed.';
            });
    }

    /**
     * Retourne la description du site.
     *
     * @return string
     */
    public function &getDefaultDescription(): string
    {
        return $this->getStringValueWithDefault('defaultDescription',
                                                function() {
                return 'PassionEngine';
            });
    }

    /**
     * Retourne les mots clés du site.
     *
     * @return string
     */
    public function &getDefaultKeywords(): string
    {
        return $this->getStringValueWithDefault('defaultKeywords',
                                                function() {
                return 'PassionEngine';
            });
    }

    /**
     * Retourne la langue par défaut.
     *
     * @return string
     */
    public function &getDefaultLanguage(): string
    {
        return $this->getStringValueWithDefault('defaultLanguage',
                                                function() {
                return 'english';
            });
    }

    /**
     * Retourne le template par défaut.
     *
     * @return string
     */
    public function &getDefaultTemplate(): string
    {
        return $this->getStringValueWithDefault('defaultTemplate',
                                                function() {
                return LibMakeStyle::DEFAULT_TEMPLATE_DIRECTORY;
            });
    }

    /**
     * Retourne le nom du module par défaut.
     *
     * @return string
     */
    public function &getDefaultModule(): string
    {
        return $this->getStringValueWithDefault('defaultModule',
                                                function() {
                return 'home';
            });
    }

    /**
     * Chargement de la configuration spécifique (via fichier).
     *
     * @param array $rawConfig
     */
    private function loadSpecificConfig(array $rawConfig): void
    {
        $newConfig = array();

        // Vérification de l'adresse email du webmaster
        if (!ExecEmail::isValidEmail($rawConfig['webmasterEmail'])) {
            CoreLogger::addDebug('Default email isn\'t valid');
        }

        define('PASSION_ENGINE_EMAIL',
               $rawConfig['webmasterEmail']);

        // Vérification du statut
        $rawConfig['websiteStatus'] = strtolower($rawConfig['websiteStatus']);

        if ($rawConfig['websiteStatus'] !== 'close' && $rawConfig['websiteStatus'] !== 'open') {
            $rawConfig['websiteStatus'] = 'open';
        }

        define('PASSION_ENGINE_STATUT',
               $rawConfig['websiteStatus']);

        // Vérification de la durée de validité du cache
        if (!is_int($rawConfig['sessionTimeLimit']) || $rawConfig['sessionTimeLimit'] < 1) {
            $rawConfig['sessionTimeLimit'] = 7;
        }

        $newConfig['sessionTimeLimit'] = (int) $rawConfig['sessionTimeLimit'];

        // Vérification du préfixage des cookies
        if (empty($rawConfig['cookiePrefix'])) {
            $rawConfig['cookiePrefix'] = 'pe';
        }

        $newConfig['cookiePrefix'] = $rawConfig['cookiePrefix'];

        // Vérification de la clé de cryptage
        if (!empty($rawConfig['cryptKey'])) {
            $newConfig['cryptKey'] = $rawConfig['cryptKey'];
        }

        // Ajout à la configuration courante
        $this->addConfig($newConfig);
    }

    /**
     * Retourne la valeur de la configuration avec prise en charge d'une valeur par défaut.
     *
     * @param string $keyName
     * @param Closure $callback
     * @return string
     */
    private
        function &getStringValueWithDefault(string $keyName,
                                            Closure $callback): string
    {
        $value = $this->getString($keyName);

        if (empty($value)) {
            $value = $callback();
            $this->setDataValue($keyName,
                                $value);
        }
        return $value;
    }
}