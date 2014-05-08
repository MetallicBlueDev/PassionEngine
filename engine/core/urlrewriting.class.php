<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire URL REWRITING
 * 
 * @author S�bastien Villemain
 *
 */
class Core_UrlRewriting {
	
	/**
	 * V�rifie si l'url rewriting a �t� activ�
	 * 
	 * @return boolean true c'est activ�
	 */
	private static function &isActived() {
		if (Core_Main::doUrlRewriting() && self::testPassed()) {
			return true;
		}
		return false;
	}
	
	/**
	 * V�rifie si la classe peut �tre activ�
	 * Alias de isActived()
	 * 
	 * @return boolean
	 */
	public static function &test() {
		return self::isActived();
	}
	
	/**
	 * V�rifie si les tests ont �t� pass�s avec succ�s
	 * 
	 * @return boolean
	 */
	private static function &testPassed() {
		// TODO v�rifie si fichier tmp de test est OK
		// si pas OK et pas de fichier tmp pour signaler la d�sactivation
		// on tente de mettre urlRewriting a 0 puis on cr�� le fichier tmp de d�sactivation
		// si d�j� fichier tmp de d�castivation ajouter erreur dans Core_Exception
		return false;
	}
	
	public static function &rewriteLink($link) {
		return $link;
	}
	
	public static function &rewriteBuffer($buffer) {
		return $buffer;
	}
}
?>