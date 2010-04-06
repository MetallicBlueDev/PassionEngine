<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Classe principal du moteur
 * 
 * @author S�bastien Villemain
 *
 */
class Core_Main {
	
	/**
	 * Tableau d'information de configuration
	 */ 
	public static $coreConfig;
	
	/**
	 * Mode de mise en page courante
	 * default : affichage normale et complet
	 * module : affichage uniquement du module si javascript activ�
	 * block : affichage uniquement du block si javascript activ�
	 * modulepage : affichage uniquement du module forc� 
	 * blockpage : affichage uniquement du block forc�
	 * 
	 * @var String
	 */
	public static $layout = "default";
	
	/**
	 * Pr�paration TR ENGINE
	 * Proc�dure de pr�paration du moteur
	 * Une �tape avant le d�marrage r�el
	 */
	public function __construct() {
		if (Core_Secure::isDebuggingMode()) Exec_Marker::startTimer("core");
		
		// Utilitaire
		Core_Loader::classLoader("Exec_Utils");
		
		// Charge le gestionnaire d'exception
		Core_Loader::classLoader("Core_Exception");
		
		// Chargement du convertiseur d'entities
		Core_Loader::classLoader("Exec_Entities");
		
		// Chargement de la configuration
		Core_Loader::classLoader("Core_ConfigsLoader");
		new Core_ConfigsLoader();
		
		// Chargement du gestionnaire d'acc�s url
		Core_Loader::classLoader("Core_Request");
		
		// Charge la session
		$this->loadSession();
		
		if (Core_Secure::isDebuggingMode()) Exec_Marker::stopTimer("core");
	}
	
	/**
	 * Ajoute l'objet a la configuration
	 * 
	 * @param array
	 */
	public static function addToConfiguration($configuration = array()) {
		if (is_array($configuration) && !empty($configuration)) {
			foreach($configuration as $key => $value) {
				self::$coreConfig[$key] = Exec_Entities::stripSlashes($value);
			}
		}
	}
	
	/**
	 * D�marrage TR ENGINE
	 */
	public function start() {
		if (Core_Secure::isDebuggingMode()) Exec_Marker::startTimer("launcher");
		
		// Chargement du moteur de traduction
		Core_Loader::classLoader("Core_Translate");
		Core_Translate::makeInstance();
		
		// Chargement du traitement HTML
		Core_Loader::classLoader("Core_TextEditor");
		
		// V�rification des bannissements
		Core_Loader::classLoader("Core_BlackBan");
		Core_BlackBan::checkBlackBan();
		
		// Chargement du gestionnaire HTML
		Core_Loader::classLoader("Core_Html");
		Core_Html::getInstance();
		
		// Configure les informations de page demand�es
		$this->loadLayout();
		$this->loadModule();
		$this->loadMakeStyle();
		
		if (!Core_Secure::isDebuggingMode()) $this->compressionOpen();
		
		// Comportement different en fonction du type de client
		if (!Core_BlackBan::isBlackUser()) {
			// Chargement du gestionnaire d'autorisation
			Core_Loader::classLoader("Core_Access");
			
			// Chargement des blocks
			Core_Loader::classLoader("Libs_Block");
			
			// Chargement de la r��criture d'URL
			if (self::doUrlRewriting()) {
				Core_Loader::classLoader("Core_UrlRewriting");
				Core_UrlRewriting::test();
			}
			
			if (self::isFullScreen() && Core_Loader::isCallable("Libs_Block") && Core_Loader::isCallable("Libs_Module")) {
				if ($this->inMaintenance()) { // Affichage site ferm�
					// Charge le block login
					Libs_Block::getInstance()->launchOneBlock(array("type = 'login'"));
					
					// Affichage des donn�es de la page de maintenance (fermeture)
					$libsMakeStyle = new Libs_MakeStyle();
					$libsMakeStyle->assign("closeText", ERROR_DEBUG_CLOSE);
					$libsMakeStyle->display("close.tpl");
				} else {
					// Traduction du module
					Core_Translate::translate("modules/" . Libs_Module::$module);
					
					// Chargement et construction du fil d'ariane
					Core_Loader::classLoader("Libs_Breadcrumb");
					Libs_Breadcrumb::getInstance();
					
					Libs_Block::getInstance()->launchAllBlock();
					Libs_Module::getInstance()->launch();
					
					Exec_Marker::stopTimer("main");
					$libsMakeStyle = new Libs_MakeStyle();
					$libsMakeStyle->display("index.tpl");
				}
			} else {
				// Affichage autonome des modules et blocks
				if (self::isModuleScreen() && Core_Loader::isCallable("Libs_Module")) {
					Core_Translate::translate("modules/" . Libs_Module::$module);
					Libs_Module::getInstance()->launch();
					echo Libs_Module::getInstance()->getModule();
				} else if (self::isBlockScreen() && Core_Loader::isCallable("Libs_Block")) {
					Libs_Block::getInstance()->launchOneBlock();
					echo Libs_Block::getInstance()->getBlock();
				}
				// Execute la commande de r�cup�ration d'erreur
				Core_Exception::getMinorError();
				// Javascript autonome
				Core_Html::getInstance()->selfJavascript();
			}
			
			// Validation du cache / Routine du cache
			Core_CacheBuffer::valideCacheBuffer();
			// Assemble tous les messages d'erreurs dans un fichier log
			Core_Exception::logException();
		} else {
			Core_BlackBan::displayBlackPage();
		}
		
		if (Core_Secure::isDebuggingMode()) {
			Exec_Marker::stopTimer("launcher");
		} else {
			$this->compressionClose();
		}
	}
	
	/**
	 * Recherche de nouveau composant
	 * 
	 * @return boolean true nouveau composant d�tect�
	 */
	public function newComponentDetected() {
		// TODO d�tection de nouveau module a coder
		return false;
	}
	
	/**
	 * D�marrage de l'installeur
	 */
	public function install() {
		// TODO installation a coder
		$installPath = TR_ENGINE_DIR . "/install/index.php";
		if (is_file($installPath)) {
			require($installPath);
		}
	}
	
	/**
	 * V�rifie l'�tat de maintenance
	 * 
	 * @return boolean
	 */
	private function inMaintenance() {
		return (self::isClosed() && Core_Session::$userRank < 2);
	}
	
	/**
	 * Lance le tampon de sortie
	 * Ent�te & tamporisation de sortie
	 */
	private function compressionOpen() {
		header("Vary: Cookie, Accept-Encoding");
		if (extension_loaded('zlib') 
				&& !ini_get('zlib.output_compression') 
				&& function_exists("ob_gzhandler") 
				&& !self::doUrlRewriting()) {
			ob_start("ob_gzhandler");
		} else {
			ob_start();
		}
	}
	
	/**
	 * Relachement des tampons de sortie
	 */
	private function compressionClose() {
		while (ob_end_flush());
	}
	
	/**
	 * Assignation et v�rification de fonction layout
	 */
	private function loadLayout() {
		// Assignation et v�rification de fonction layout
		$layout = strtolower(Core_Request::getWord("layout"));
		
		// Configuration du layout
		if ($layout != "default" && $layout != "modulepage"	&& $layout != "blockpage" 
				&& (($layout != "block" && $layout != "module") || (!Core_Html::getInstance()->isJavascriptEnabled()))) {
			$layout = "default";
		}
		self::$layout = $layout;
	}
	
	/**
	 * Cr�ation de l'instance du module
	 */
	private function loadModule() {
		Core_Loader::classLoader("Libs_Module");	
		Libs_Module::getInstance(
			Core_Request::getWord("mod"), 
			Core_Request::getWord("page"), 
			Core_Request::getWord("view")
		);
	}
	
	/**
	 * Assignation et v�rification du template
	 */
	private function loadMakeStyle() {		
		$template = (!Core_Session::$userTemplate) ? self::$coreConfig['defaultTemplate'] : Core_Session::$userTemplate;
		Core_Loader::classLoader("Libs_MakeStyle");		
		Libs_MakeStyle::getCurrentTemplate($template);
	}
	
	/**
	 * Charge les outils de session
	 */
	private function loadSession() {
		// Gestionnaire des cookie
		Core_Loader::classLoader("Exec_Cookie");
		
		// Chargement de l'outil de cryptage
		Core_Loader::classLoader("Exec_Crypt");
		
		// Analyse pour les statistiques
		Core_Loader::classLoader("Exec_Agent");
		Exec_Agent::getVisitorsStats();
		
		// Chargement des sessions
		Core_Loader::classLoader("Core_Session");
		Core_Session::getInstance();
	}
		
	/**
	 * V�rifie si l'affichage se fait en �cran complet
	 * 
	 * @return boolean true c'est en plein �cran
	 */
	public static function isFullScreen() {
		return ((self::$layout == "default") ? true : false);
	}
	
	/**
	 * V�rifie si l'affichage se fait en �cran minimal cibl� module
	 * 
	 * @return boolean true c'est un affichage de module uniquement
	 */
	public static function isModuleScreen() {
		return ((self::$layout == "module" || self::$layout == "modulepage") ? true : false);
	}
	
	/**
	 * V�rifie si l'affichage se fait en �cran minimal cibl� block
	 * 
	 * @return boolean true c'est un affichage de block uniquement
	 */
	public static function isBlockScreen() {
		return ((self::$layout == "block" || self::$layout == "blockpage") ? true : false);
	}
	
	/**
	 * V�rifie si l'url rewriting est activ�
	 * 
	 * @return boolean
	 */
	public static function doUrlRewriting() {
		return (self::$coreConfig['urlRewriting'] == 1) ? true : false;
	}
	
	/**
	 * Etat des inscriptions au site
	 * 
	 * @return boolean
	 */
	public static function isRegistrationAllowed() {
		return (self::$coreConfig['registrationAllowed'] == 1) ? true : false;
	}
	
	/**
	 * V�rifie si le site est ferm�
	 * 
	 * @return boolean
	 */
	public static function isClosed() {
		return (self::$coreConfig['defaultSiteStatut'] == "close") ? true : false;
	}
}
?>