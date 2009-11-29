<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de email
 * 
 * @author S�bastien Villemain
 *
 */
class Exec_Mailer {
	
	/**
	 * V�rifie la validit� du mail
	 * 
	 * @param $address adresse email a v�rifier
	 * @return boolean true l'adresse email est valide
	 */
	public static function validMail($address) {
		return (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? true : false;
	}
	
	/**
	 * Retourne une chaine ou une image g�n�r�
	 * 
	 * @param $mail
	 * @param $name
	 * @return String or String with HTML TAG
	 */
	public static function &protectedDisplay($mail, $name = "mail") {
		$mail = str_replace("@", "_AT_", $mail);
		$mail = str_replace(".", "_POINT_", $mail);
		$protected = "<a href=\"mailto:" . $mail . "\">" . $name . "</a>";
		
		// On utilise la librairie GD
		if (@extension_loaded('gd')) {
			return "<img src=\"engine/libs/imagegenerator.get.php?mode=text&amp;text=" . urlencode(base64_encode($mail)) . "\" alt=\"\" title=\"" . $name . "\" />";
		} else {
			return $protected;
		}
	}
	
	public static function sendMail() {
		
	}
}
?>