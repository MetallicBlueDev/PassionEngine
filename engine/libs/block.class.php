<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de blocks
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Block {
	
	/**
	 * Gestionnnaire de blocks
	 * 
	 * @var Libs_Block
	 */
	private static $libsBlock = null;
	
	/**
	 * Blocks charg�s, tableau a deux dimensions
	 * 
	 * @var array
	 */
	public static $blocksConfig = array();
	
	/**
	 * Blocks compil�s, tableau a deux dimensions
	 * 
	 * @var array
	 */
	private $blocksCompiled = array();
	
	public function __construct() {
	}
	
	/**
	 * Instance du gestionnaire de block
	 * 
	 * @return Libs_Block
	 */
	public static function &getInstance() {
		if (self::$libsBlock == null) {			
			self::$libsBlock = new self();
		}
		return self::$libsBlock;
	}
	
	/**
	 * V�rifie si le block est valide
	 * 
	 * @param $blockType String
	 * @return boolean true block valide
	 */
	private function isBlock($blockType) {
		return is_file(TR_ENGINE_DIR . "/blocks/" . $blockType . ".block.php");
	}
	
	/**
	 * V�rifie si le block doit �tre activ�
	 * 
	 * @param $modules array
	 * @return boolean true le block doit �tre actif
	 */
	private function blockActiveMod($modules = array("all")) {
		if (Core_Loader::isCallable("Libs_Module")) {
			foreach ($modules as $modSelected)  {
				if (Libs_Module::$module == $modSelected || $modSelected == "all") {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Charge les blocks
	 */
	// TODO mettre en cache la requete
	public function launchAllBlock() {
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rank", "mods"),
			array("side > 0", "&& rank >= 0"),
			array("side", "position")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			// R�cuperation des donn�es des blocks
			Core_Sql::addBuffer("block");
			while ($block = Core_Sql::fetchBuffer("block")) {
				$block->mods = explode("|", $block->mods);
				
				if ($this->isBlock($block->type) // Si le block existe
						&& $this->blockActiveMod($block->mods) // Et qu'il est actif sur la page courante
						&& Core_Session::$userRank >= $block->rank) { // Et que le client est assez grad�
					$block->title = Exec_Entities::textDisplay($block->title);
					
					self::$blocksConfig[$block->side][] = $block;
					$this->get($block);
				}
			}
		}
	}
	
	/**
	 * Charge un block
	 * 
	 * @param $where array exemple array(block_id = '0')
	 */
	// TODO mettre en cache la requete
	public function launchOneBlock($where = array()) {
		if (empty($where)) { // Capture de la variable
			$where = array("block_id = '" . Core_Request::getInt("block") . "'");
		}
		
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rank", "mods"),
			$where
		);
		if (Core_Sql::affectedRows() > 0) {
			$block = Core_Sql::fetchObject();
			
			if ($this->isBlock($block->type) // Si le block existe
					&& Core_Session::$userRank >= $block->rank) { // Et que le client est assez grad�
				$block->title = Exec_Entities::textDisplay($block->title);
				
				self::$blocksConfig[$block->side][] = $block;
				$this->get($block);
			}
		}
	}
	
	/**
	 * R�cup�re le block
	 * 
	 * @param $block array
	 */
	private function get($block) {
		// V�rification de l'acc�s
		if (Core_Access::autorize("block" . $block->block_id, $block->rank)) {
			$blockClassName = "Block_" . ucfirst($block->type);
			$loaded = Core_Loader::classLoader($blockClassName);
			
			// V�rification du block
			if ($loaded) {
				if (Core_Loader::isCallable($blockClassName, "display")) {
					Core_Translate::translate("blocks/" . $block->type);
					$BlockClass = new $blockClassName();
					$BlockClass->blockId = $block->block_id;
					$BlockClass->side = $block->side;
					$BlockClass->sideName = self::getSideLetters($block->side);
					$BlockClass->templateName = "block_" . $BlockClass->sideName;
					$BlockClass->title = $block->title;
					$BlockClass->content = $block->content;
					$BlockClass->rank = $block->rank;
					
					// Capture des donn�es d'affichage
					ob_start();
					$BlockClass->display();
						$this->blocksCompiled[$block->side][] = ob_get_contents();
						ob_end_clean();
				} else {
					Core_Exception::addAlertError(ERROR_BLOCK_CODE);
				}
			}
		}
	}
	
	/**
	 * Retourne les blocks compil�s voulu (right/left/top/bottom)
	 * 
	 * @param $side String
	 * @return String
	 */
	public function &getBlocks($side) {
		// Conversion du side en identifiant num�rique
		$sidePosition = is_string($side) ? self::getSideNumeric($side) : $side;
		
		$blockSide = "";
		if (isset($this->blocksCompiled[$sidePosition])) {
			foreach($this->blocksCompiled[$sidePosition] as $key => $block) {
				$blockSide .= $this->outPut($block, $this->doRewriteBuffer($sidePosition, $key));
			}
		}
		return $blockSide;
	}
	
	/**
	 * Retourne le type d'orientation/postion en lettres
	 * 
	 * @param $side int
	 * @return String identifiant de la position (right, left...)
	 */
	private static function &getSideLetters($side) {
		$sideLetters = $side; // Assignation par d�faut
		
		// Si on renseigne bien un entier
		if (is_numeric($side)) { // On recherche la position
			switch ($side) {
				case 1: $sideLetters = "right"; break;
				case 2: $sideLetters = "left"; break;
				case 3: $sideLetters = "top"; break;
				case 4: $sideLetters = "bottom"; break;
				case 5: $sideLetters = "moduletop"; break;
				case 6: $sideLetters = "modulebottom"; break;
				default : Core_Secure::getInstance()->debug("blockSide", "Numeric side:" . $side);
			}
		}
		return $sideLetters;
	}
	
	/**
	 * Retourne le type d'orientation/position en chiffre
	 * 
	 * @param $side String
	 * @return int identifiant de la position (1, 2..)
	 */
	private static function &getSideNumeric($side) {
		$sideNumeric = $side; // Assignation par d�faut
		
		// Si on renseigne bien un string
		if (is_string($side)) { // On recherche la position
			switch ($side) {
				case 'right': $sideNumeric = 1; break;
				case 'left': $sideNumeric = 2; break;
				case 'top': $sideNumeric = 3; break;
				case 'bottom': $sideNumeric = 4; break;
				case 'moduletop': $sideNumeric = 5; break;
				case 'modulebottom': $sideNumeric = 6; break;
				default : Core_Secure::getInstance()->debug("blockSide", "Letters side:" . $side);
			}
		}
		return $sideNumeric;
	}
	
	/**
	 * Liste des orientations possible
	 * 
	 * @return array("numeric" => identifiant int, "letters" => nom de la position)
	 */
	public static function &listSide() {
		$sideList = array();
		for ($i = 1; $i < 7; $i++) {
			$sideList[] = array("numeric" => $i, "letters" => self::getLitteralSide($i));
		}
		return $sideList;
	}
	
	/**
	 * Retourne le type d'orientation avec la traduction
	 * 
	 * @param $side int or String
	 * @return String postion traduit (si possible)
	 */
	public static function &getLitteralSide($side) {
		$side = strtoupper(self::getSideLetters($side));
		$side = defined($side) ? constant($side) : $side;
		return $side;
	}
	
	/**
	 * R��criture du tampon de sortie si besoin
	 * 
	 * @param $buffer String
	 * @return $buffer String
	 */
	private function &outPut(&$buffer, $rewriteBuffer = false) {
		if (Core_Main::doUrlRewriting() && $rewriteBuffer) {
			$buffer = Core_UrlRewriting::rewriteBuffer($buffer);
		}
		return $buffer;
	}
	
	/**
	 * Recherche le parametre indiquant qu'il doit y avoir une r��criture du buffer
	 * 
	 * @param $side int cot� ou se trouve le block
	 * @param $key int cles du block
	 * @return boolean true il doit y avoir r��criture
	 */
	private function doRewriteBuffer($side, $key) {
		return (strpos(self::$blocksConfig[$side][$key]->content, "rewriteBuffer") !== false) ? true : false;
	}
	
	/**
	 * Retourne le block compil�
	 * 
	 * @return String
	 */
	public function getBlock() {
		foreach($this->blocksCompiled as $side => $compiled) {
			return $this->outPut($this->blocksCompiled[$side][0], $this->doRewriteBuffer($side, 0));
		}
	}
	
	/**
	 * Retourne la liste des blocks disponibles
	 * 
	 * @return array
	 */
	public static function &listBlocks() {
		$blockList = array();
		$files = Core_CacheBuffer::listNames("blocks");
		foreach($files as $key => $fileName) {
			// Nettoyage du nom de la page
			$pos = strpos($fileName, ".block");
			// Si c'est un block
			if ($pos !== false && $pos > 0) {
				$blockList[] = substr($fileName, 0, $pos);
			}
		}
		return $blockList;
	}
}

/**
 * Block de base, h�rit� par tous les autres blocks
 * Mod�le pour le contenu d'un block
 * 
 * @author Sebastien Villemain
 *
 */
abstract class Block_Model {
	
	/**
	 * Identifiant du block
	 * 
	 * @var int
	 */
	public $blockId = 0;
	
	/**
	 * Position du block en chiffre
	 * 
	 * @var int
	 */
	public $side = 0;
	
	/**
	 * Position du block en lettre
	 * 
	 * @var String
	 */
	public $sideName = "";
	
	/**
	 * Nom complet du template de block a utiliser
	 * 
	 * @var String
	 */
	public $templateName = "";
	
	/**
	 * Titre du block
	 * 
	 * @var String
	 */
	public $title = "";
	
	/**
	 * Contenu du block
	 * 
	 * @var String
	 */
	public $content = "";
	
	/**
	 * Rank pour acceder au block
	 * 
	 * @var int
	 */
	public $rank = "";
	
	/**
	 * Affichage par d�faut
	 */
	public function display() {
		Core_Exception::addAlertError(ERROR_BLOCK_IMPLEMENT . ((!empty($this->title)) ? " (" . $this->title . ")" : ""));
	}
}
?>