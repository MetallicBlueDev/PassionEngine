<?php

namespace TREngine\Engine\Exec;

/**
 * Outil de manipulation des emails.
 *
 * @author Sébastien Villemain
 */
class ExecEmail
{

    /**
     * Vérifie la validité de l'mail.
     *
     * @param string $email L'adresse email à vérifier
     * @return bool true L'adresse email est valide
     */
    public static function isValidEmail(string $email): bool
    {
        return (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",
                           $email)) ? true : false;
    }

    /**
     * Retourne une chaine ou une image généré
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    public static function &displayEmail(string $email,
                                         string $name = "email"): string
    {
        $rslt = "";
        $email = str_replace("@",
                             "_AT_",
                             $email);
        $email = str_replace(".",
                             "_DOT_",
                             $email);
        $protected = "<a href=\"mailto:" . $email . "\">" . $name . "</a>";

        if (extension_loaded('gd')) {
            $rslt = "<img src=\"index.php?layout=block&amp;blockType=ImageGenerator&amp;mode=text&amp;text=" . urlencode(base64_encode($email)) . "\" alt=\"\" title=\"" . $name . "\" />";
        } else {
            $rslt = $protected;
        }
        return $rslt;
    }

    public static function sendEmail(): bool
    {
        return false;
    }
}