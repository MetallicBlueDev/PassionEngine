<?php
if (!defined("TR_ENGINE_INDEX")) {
	new Core_Secure();
}

/**
 * Syst�me de s�curit�
 * Analyse rapidement les donn�es recues
 * Configure les erreurs
 * Capture la configuration
 * 
 * @author S�bastien Villemain
 *
 */
class Core_Secure {
	
	/**
	 * Instance de cette classe
	 */ 
	private static $secure = false;
	
	public function __construct() {
		$this->checkError();
		$this->checkQueryString();
		$this->checkRequestReferer();
		$this->checkGPC();
		
		// Si nous ne sommes pas pass� par l'index
		if (!defined("TR_ENGINE_INDEX")) {
			if (!class_exists("Core_Info")) require("info.class.php");
			define("TR_ENGINE_INDEX", 1);
			$this->debug("badUrl");
		}
	}
	
	/**
	 * Cr�� une instance de la classe si elle n'existe pas
	 * Retourne l'instance de la classe
	 * 
	 * @return Core_Secure
	 */
	public static function &getInstance() {
		if (self::$secure === false) {
			self::$secure = new self();
		}
		return self::$secure;
	}
	
	/**
	 * R�glages des sorties d'erreurs
	 */
	private function checkError() {
		// R�glages des sorties d'erreur
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
	}
	
	/**
	 * V�rification des donn�es recus (Query String)
	 */
	private function checkQueryString() {
		$queryString = strtolower(rawurldecode($_SERVER['QUERY_STRING']));
		$badString = array("SELECT", "UNION", 
			"INSERT", "UPDATE", "AND", 
			"%20union%20", "/*", "*/union/*", 
			"+union+", "load_file", "outfile", 
			"document.cookie", "onmouse", "<script", 
			"<iframe", "<applet", "<meta", "<style", 
			"<form", "<img", "<body", "<link");
		
		foreach ($badString as $stringValue) {
			if (strpos($queryString, $stringValue)) $this->debug();
		}
	}
	
	/**
	 * V�rification des envoies POST
	 */
	private function checkRequestReferer() {
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			if (!empty($_SERVER['HTTP_REFERER'])) { 
				if (!preg_match("/" . $_SERVER['HTTP_HOST'] . "/", $_SERVER['HTTP_REFERER'])) $this->debug();
			}
		}
	}
	
	/**
	 * Fonction de substitution pour MAGIC_QUOTES_GPC
	 */
	private function checkGPC() {
		if (function_exists("magic_quotes_runtime") 
				&& function_exists("set_magic_quotes_runtime")) {
			set_magic_quotes_runtime(0);
		}
		
		$GPC = array("_GET", "_POST", "_COOKIE");
		foreach ($GPC as $KEY) $this->addSlashesForQuotes($$KEY);
	}
	
	/**
	 * Ajoute un antislash pour chaque quote
	 * 
	 * @param $key objet sans antislash
	 */
	private function addSlashesForQuotes($key) {
		if (is_array($key)) {
			while (list($k, $v) = each($key)) {
				// Ajout 
				if (is_array($key[$k])) $this->addSlashesForQuotes($key[$k]);
				else $key[$k] = addslashes($v);
			}
			reset($key);
		} else {
			$key = addslashes($key);
		}
	}
	
	/**
	 * La fonction debug affiche un message d'erreur
	 * Cette fonction est activ� si une erreur est d�tect�
	 * 
	 * @param $ie L'exception interne lev�e
	 */
	public function debug($ie = "") {
		// Charge le loader si il faut
		if (!class_exists("Core_Loader")) {
			require(TR_ENGINE_DIR . "/engine/core/loader.class.php");
		}
		
		// Gestionnaires suivants obligatoires
		Core_Loader::classLoader("Core_Request");
		Core_Loader::classLoader("Exec_Cookie");
		Core_Loader::classLoader("Exec_Crypt");
		Core_Loader::classLoader("Core_Translate");
		Core_Translate::makeInstance();
		Core_Loader::classLoader("Core_Html");
		Core_Loader::classLoader("Libs_MakeStyle");
		
		// Pr�paration du template debug
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("errorMessageTitle", $this->getErrorMessageTitle($ie));
		$libsMakeStyle->assign("errorMessage", $this->getDebugMessage($ie));
		// Affichage du template en debug si probl�me
		$libsMakeStyle->displayDebug("debug.tpl");	
		
		exit();
	}
	
	/**
	 * Analyse l'erreur et pepare l'affichage de l'erreur
	 * 
	 * @param $ie L'exception interne lev�e
	 * @return String $errorMessage
	 */
	private function getDebugMessage($ie) {
		// Tableau avec les lignes d'erreurs
		$errorMessage = array();
		// Si l'exception est bien pr�sente
		if (is_object($ie)) {
			$trace = $ie->getTrace();
			$nbTrace = count($trace);
			
			for ($i = $nbTrace; $i > 0; $i--) {
				// Erreur courante
				$errorLine = "";
				if (is_array($trace[$i])) {
					foreach($trace[$i] as $key => $value) {
						if ($key == "file") {
							$value = preg_replace("/([a-zA-Z0-9.]+).php/", "<b>\\1</b>.php", $value);
							$errorLine .= " <b>" . $key . "</b> " . $value;
						} else if ($key == "line" || $key == "class") {
							$errorLine .= " in <b>" . $key . "</b> " . $value;
						}					
					}
				}
				// Remplissage dans avec les autres erreurs
				if (!empty($errorLine)) {
					$errorMessage[] = $errorLine;
				}
			}
		}
		if (Core_Loader::isCallable("Core_Session") && Core_Loader::isCallable("Core_Sql") && Core_Session::$userRang > 1) {
			$errorMessage = array_merge($errorMessage, (array) Core_Sql::getLastError());
		}
		return $errorMessage;
	}
	
	/**
	 * Retourne le type d'erreur courant sous forme de message
	 * 
	 * @param $cmd
	 * @return String $errorMessageTitle
	 */
	private function getErrorMessageTitle($ie) {
		// En fonction du type d'erreur
		if (is_object($ie)) $cmd = $ie->getMessage();
		else $cmd = $ie;
		
		// Message d'erreur
		$errorMessageTitle = "ERROR_DEBUG_" . strtoupper($cmd);
		
		if (defined($errorMessageTitle)) {
			return constant($errorMessageTitle);
		} else {
			return "Stop loading (Fatal error unknown).";
		}
	}
}

?>