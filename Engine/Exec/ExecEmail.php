<?php

namespace PassionEngine\Engine\Exec;

use PassionEngine\Engine\Core\CoreLayout;

/**
 * Outil de manipulation des mails.
 *
 * @author Sébastien Villemain
 */
class ExecEmail
{

    /**
     * Vérifie la validité du mail.
     *
     * @param string $email L'adresse mail à vérifier
     * @return bool L'adresse mail est valide
     */
    public static function isValidEmail(string $email): bool
    {
        return (preg_match('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                           $email)) ? true : false;
    }

    /**
     * Retourne une chaîne ou une image généré.
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    public static function &displayEmail(string $email,
                                         string $name = 'email'): string
    {
        $rslt = '';
        $email = str_replace('@',
                             '_AT_',
                             $email);
        $email = str_replace('.',
                             '_DOT_',
                             $email);
        $protected = '<a href="mailto:' . $email . '">' . $name . '</a>';

        if (extension_loaded('gd')) {
            $rslt = '<img src="index.php?' . CoreLayout::REQUEST_LAYOUT . '=' . CoreLayout::BLOCK . '&amp;' . CoreLayout::REQUEST_BLOCKTYPE . '=ImageGenerator&amp;mode=text&amp;text=' . urlencode(base64_encode($email)) . '" alt="" title="' . $name . '" />';
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