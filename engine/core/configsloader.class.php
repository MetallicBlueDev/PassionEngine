<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Chargeur de configuration
 * 
 * @author S�bastien Villemain
 *
 */
class Core_ConfigsLoader {
	
	/**
	 * Tableau contenant les r�glages valides
	 */
	private $config = array();
	
	/**
	 * Tableau d'information sur la base de donn�e
	 */
	private $database = array();
	
	/**
	 * Lance le chargeur de configs
	 */
	public function __construct() {
		$this->loadConfigFile();
		$this->loadDatabaseFile();
		$this->loadFtpFile();
		$this->loadVersion();
	}
	
	/**
	 * Charge et v�rifie la configuration
	 */
	private function loadConfigFile() {
		
		$configPath = TR_ENGINE_DIR . "/configs/config.inc.php";
		
		if (is_file($configPath)) {
			require($configPath);
			
			// V�rification de l'adresse email du webmaster
			Core_Loader::classLoader("Exec_Mailer");
			if (Exec_Mailer::validMail($config["TR_ENGINE_MAIL"])) {
				define("TR_ENGINE_MAIL", $config["TR_ENGINE_MAIL"]);
			} else {
				Core_Exception::setException("Default mail isn't valide");
			}
			
			// V�rification du statut
			$config["TR_ENGINE_STATUT"] = strtolower($config["TR_ENGINE_STATUT"]);
			if ($config["TR_ENGINE_STATUT"] == "close") define("TR_ENGINE_STATUT", "close");
			else define("TR_ENGINE_STATUT", "open");
			
			// Recherche du statut du site
			if (TR_ENGINE_STATUT == "close") {
				Core_Secure::getInstance()->debug("close");
			}
			
			if (is_int($config['cacheTimeLimit']) && $config['cacheTimeLimit'] >= 1) $this->config['cacheTimeLimit'] = $config['cacheTimeLimit'];
			else $this->config['cacheTimeLimit'] = 7;
			
			if (!empty($config['cookiePrefix'])) $this->config['cookiePrefix'] = $config['cookiePrefix'];
			else $this->config['cookiePrefix'] = "tr";
			
			if (!empty($config['cryptKey'])) $this->config['cryptKey'] = $config['cryptKey'];
		} else {
			Core_Secure::getInstance()->debug("configPath", $configPath);
		}
	}
	
	/**
	 * Charge et v�rifie les informations de base de donn�e
	 */
	private function loadDatabaseFile() {
		
		$databasePath = TR_ENGINE_DIR . "/configs/database.inc.php";
		
		if (is_file($databasePath)) {
			require($databasePath);
			
			if (!is_file(TR_ENGINE_DIR . "/engine/base/" . $db['type'] . ".class.php")) {
				Core_Secure::getInstance()->debug("sqltype");
			}
			
			$this->database = $db;
		} else {
			Core_Secure::getInstance()->debug("sqlPath");
		}
	}
	
	/**
	 * Charge les donn�es FTP
	 */
	private function loadFtpFile() {
		$modes = array("php");
		$ftpPath = TR_ENGINE_DIR . "/configs/ftp.inc.php";
		
		if (is_file($ftpPath)) {
			require($ftpPath);
			
			// Pr�paration de l'activation du mode
			$mode = strtolower($ftp['type']);
			if ($mode != "php") $modes[] = $mode;
			// Ajout des donn�es FTP
			Core_CacheBuffer::setFtp($ftp);
		}		
		// Activation des modes
		Core_CacheBuffer::setModeActived($modes);
	}
	
	/**
	 * Charge le num�ro de version du moteur
	 */
	private function loadVersion() {
		$versionPath = TR_ENGINE_DIR . "/engine/version.inc.php";
		
		$TR_ENGINE_VERSION = "0.0.0.0";
		if (is_file($versionPath)) {
			require($versionPath);
		}
		define("TR_ENGINE_VERSION", $TR_ENGINE_VERSION);
	}
	
	/**
	 * Retourne la configuration
	 * @return String
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Retourne la configuration de la base de donn�e
	 * @return String
	 */
	public function getDatabase() {
		return $this->database;
	}
	
	public function __destruct() {
		unset($this->config);
		unset($this->database);
	}
}

?>