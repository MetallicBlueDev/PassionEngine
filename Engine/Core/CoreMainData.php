<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Exec\ExecMailer;
use TREngine\Engine\Exec\ExecString;
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
        $rawConfig = $this->getArrayValues("configs_config");

        if (!empty($rawConfig)) {
            $this->loadSpecificConfig($rawConfig);
            $canUse = true;
        }

        // Nettoyage des clés temporaires
        $this->unsetValue("configs_config");
        return $canUse;
    }

    /**
     * Ajoute les données à la configuration.
     *
     * @param array
     */
    public function addConfig(array $configuration)
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
    public function addInclude(string $name, array $include)
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
        return $this->getArrayValues("configs_cache");
    }

    /**
     * Retourne la configuration de la base de données.
     *
     * @return array
     */
    public function &getConfigDatabase(): array
    {
        return $this->getArrayValues("configs_database");
    }

    /**
     * Détermine si l'url rewriting est activé.
     *
     * @return bool
     */
    public function &doUrlRewriting(): bool
    {
        return $this->getBoolValue("urlRewriting");
    }

    /**
     * Vérifie l'état de maintenance.
     *
     * @return bool
     */
    public function doDumb(): bool
    {
        return (!$this->doOpening() && !CoreSession::getInstance()->getUserInfos()->hasAdminRank());
    }

    /**
     * Vérifie l'état du site (ouvert/fermé).
     *
     * @return bool
     */
    public function doOpening(): bool
    {
        return ($this->getDefaultSiteStatut() === "open");
    }

    /**
     * Détermine l'état des inscriptions au site.
     *
     * @return bool
     */
    public function &registrationAllowed(): bool
    {
        return $this->getBoolValue("registrationAllowed");
    }

    /**
     * Retourne le préfixe des cookies.
     *
     * @return string
     */
    public function &getCookiePrefix(): string
    {
        return $this->getStringValue("cookiePrefix");
    }

    /**
     * Retourne la durée de validité du cache des sessions.
     *
     * @return int
     */
    public function &getSessionTimeLimit(): int
    {
        return $this->getIntValue("sessionTimeLimit");
    }

    /**
     * Retourne la clé de cryptage.
     *
     * @return string
     */
    public function &getCryptKey(): string
    {
        return $this->getStringValue("cryptKey");
    }

    /**
     * Retourne le mode du captcha.
     *
     * @return string
     */
    public function &getCaptchaMode(): string
    {
        return $this->getStringValue("captchaMode");
    }

    /**
     * Retourne l'adresse email de l'administrateur.
     *
     * @return string
     */
    public function &getDefaultAdministratorMail(): string
    {
        return $this->getStringValueWithDefault("defaultAdministratorMail",
                                                function() {
                    return TR_ENGINE_MAIL;
                });
    }

    /**
     * Retourne le nom du site.
     *
     * @return string
     */
    public function &getDefaultSiteName(): string
    {
        return $this->getStringValueWithDefault("defaultSiteName",
                                                function() {
                    return CoreRequest::getString("SERVER_NAME",
                                                  "",
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
        return $this->getStringValueWithDefault("defaultSiteSlogan",
                                                function() {
                    return "TR ENGINE";
                });
    }

    /**
     * Retourne le status du site.
     *
     * @return string
     */
    public function &getDefaultSiteStatut(): string
    {
        return $this->getStringValueWithDefault("defaultSiteStatut",
                                                function() {
                    return "open";
                });
    }

    /**
     * Retourne la raison de la fermeture du site.
     *
     * @return string
     */
    public function &getDefaultSiteCloseReason(): string
    {
        return $this->getStringValueWithDefault("defaultSiteCloseReason",
                                                function() {
                    return " ";
                });
    }

    /**
     * Retourne la description du site.
     *
     * @return string
     */
    public function &getDefaultDescription(): string
    {
        return $this->getStringValueWithDefault("defaultDescription",
                                                function() {
                    return "TR ENGINE";
                });
    }

    /**
     * Retourne les mots clés du site.
     *
     * @return string
     */
    public function &getDefaultKeyWords(): string
    {
        return $this->getStringValueWithDefault("defaultKeyWords",
                                                function() {
                    return "TR ENGINE";
                });
    }

    /**
     * Retourne la langue par défaut.
     *
     * @return string
     */
    public function &getDefaultLanguage(): string
    {
        return $this->getStringValueWithDefault("defaultLanguage",
                                                function() {
                    return "english";
                });
    }

    /**
     * Retourne le template par défaut.
     *
     * @return string
     */
    public function &getDefaultTemplate(): string
    {
        return $this->getStringValueWithDefault("defaultTemplate",
                                                function() {
                    return LibMakeStyle::DEFAULT_TEMPLATE;
                });
    }

    /**
     * Retourne le nom du module par défaut.
     *
     * @return string
     */
    public function &getDefaultMod(): string
    {
        return $this->getStringValueWithDefault("defaultMod",
                                                function() {
                    return "home";
                });
    }

    /**
     * Chargement de la configuration spécifique (via fichier).
     *
     * @param array $rawConfig
     */
    private function loadSpecificConfig(array $rawConfig)
    {
        $newConfig = array();

        // Vérification de l'adresse email du webmaster
        if (!ExecMailer::isValidMail($rawConfig["TR_ENGINE_MAIL"])) {
            CoreLogger::addException("Default mail isn't valid");
        }

        define("TR_ENGINE_MAIL",
               $rawConfig["TR_ENGINE_MAIL"]);

        // Vérification du statut
        $rawConfig["TR_ENGINE_STATUT"] = strtolower($rawConfig["TR_ENGINE_STATUT"]);

        if ($rawConfig["TR_ENGINE_STATUT"] !== "close" && $rawConfig["TR_ENGINE_STATUT"] !== "open") {
            $rawConfig["TR_ENGINE_STATUT"] = "open";
        }

        define("TR_ENGINE_STATUT",
               $rawConfig["TR_ENGINE_STATUT"]);

        // Vérification de la durée de validité du cache
        if (!is_int($rawConfig['sessionTimeLimit']) || $rawConfig['sessionTimeLimit'] < 1) {
            $rawConfig['sessionTimeLimit'] = 7;
        }

        $newConfig['sessionTimeLimit'] = (int) $rawConfig['sessionTimeLimit'];

        // Vérification du préfixage des cookies
        if (empty($rawConfig['cookiePrefix'])) {
            $rawConfig['cookiePrefix'] = "tr";
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
    private function &getStringValueWithDefault(string $keyName, Closure $callback): string
    {
        $value = $this->getStringValue($keyName);

        if (empty($value)) {
            $value = $callback();
            $this->setDataValue($keyName,
                                $value);
        }
        return $value;
    }
}