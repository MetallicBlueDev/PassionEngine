<?php

if (!defined("TR_ENGINE_INDEX")) {
	require("core/secure.class.php");
	new Core_Secure();
}

/**
 * Num�ro de version du moteur
 * 
 * Controle de r�vision
 * xx -> version courante
 * xx -> fonctionnalit�es ajout�es
 * xx -> bugs ou failles critiques corrig�s
 * xx -> bug mineur
 * 
 * @var String
 */
$TR_ENGINE_VERSION = "0.1.0.0";

?>