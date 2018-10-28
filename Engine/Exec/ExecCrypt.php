<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;

/**
 * Outil de manipulation de données chiffrées.
 *
 * @author Sébastien Villemain
 */
class ExecCrypt
{

    /**
     * Création d'un identifiant unique avec des chiffres, lettres et sensible à la case.
     *
     * @param int $size
     * @return string
     */
    public static function &makeIdentifier(int $size = 32): string
    {
        $key = self::makeNewKey($size,
                                true,
                                true,
                                true);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec que des chiffres.
     *
     * @param int $size
     * @return string
     */
    public static function &makeNumericIdentifier(int $size = 32): string
    {
        $key = self::makeNewKey($size,
                                false,
                                true,
                                false);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec que des lettres en minuscule.
     *
     * @param int $size
     * @return string
     */
    public static function &makeLetterIdentifier(int $size = 32): string
    {
        $key = self::makeNewKey($size,
                                true,
                                false,
                                false);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec des lettres en majuscule et en minuscule
     *
     * @param int $size
     * @return string
     */
    public static function &makeLetterCaseSensitiveIdentifier(int $size = 32): string
    {
        $key = self::makeNewKey($size,
                                true,
                                false,
                                true);
        return $key;
    }

    /**
     * Chiffrement MD5 via la fonction <code>crypt()</code> avec clé additionnelle.
     * Chiffrement qui nécessite l'activation d'une clé coté serveur.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptBySmd5(string $data,
                                        string $salt = ''): string
    {
        $cryptData = '';

        if (defined('CRYPT_MD5') && CRYPT_MD5) {
            if (empty($salt)) {
                $salt = self::makeIdentifier(8);
            }

            $salt = substr($salt,
                           0,
                           8);
            $cryptData = crypt($data,
                               '$1$' . $salt . '$');
        } else {
            if (CoreLoader::isCallable('CoreLogger')) {
                CoreLogger::addException('Unsupported crypt method: CRYPT_MD5');
            }
            $cryptData = self::cryptByMd5Unsafe($data);
        }
        return $cryptData;
    }

    /**
     * Chiffrement standard du moteur.
     * Chiffrement équilibré (suffisant pour la plupart du temps).
     * MD5 amélioré spécialement pour le moteur.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptByStandard(string $data,
                                            string $salt = ''): string
    {
        if (empty($salt)) {
            $salt = self::makeIdentifier(8);
        }

        $salt = substr($salt,
                       0,
                       8);
        $cryptData = 'TR' . md5($data . $salt);
        return $cryptData;
    }

    /**
     * Chiffrement DES.
     * Chiffrement qualité moyenne qui nécessite l'activation d'une clé coté serveur.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptByDes(string $data,
                                       string $salt = ''): string
    {
        $cryptData = '';

        if (defined('CRYPT_STD_DES') && CRYPT_STD_DES) {
            if (empty($salt)) {
                $salt = self::makeIdentifier(2);
            }

            $salt = substr($salt,
                           0,
                           2);
            $cryptData = crypt($data,
                               $salt);
        } else {
            if (CoreLoader::isCallable('CoreLogger')) {
                CoreLogger::addException('Unsupported crypt method: CRYPT_STD_DES');
            }
            $cryptData = self::cryptBySmd5($data,
                                           $salt);
        }
        return $cryptData;
    }

    /**
     * Chiffrement SHA1.
     * Chiffrement de qualité moyenne et sans clé additionnelle.
     *
     * @param string $data
     * @return string
     */
    public static function &cryptBySha1(string $data): string
    {
        $cryptData = sha1($data);
        return $cryptData;
    }

    /**
     * Chiffrement SSHA.
     * Chiffrement de bonne qualité mais lent.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptBySsha(string $data,
                                        string $salt = ''): string
    {
        if (empty($salt)) {
            $salt = self::makeIdentifier(4);
        }

        $salt = substr($salt,
                       0,
                       4);
        $cryptData = '{SSHA}' . base64_encode(pack('H*',
                                                   sha1($data . $salt)) . $salt);
        return $cryptData;
    }

    /**
     * Chiffrement en chaîne binaire SHA1.
     * Chiffrement de bonne qualité mais lent et sans clé additionnelle.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptByBinarySha1(string $data): string
    {
        // Chaîne hexadécimale H, bit de poids fort en premier
        $cryptData = '*' . sha1(pack('H*',
                                     sha1($data)));
        return $cryptData;
    }

    /**
     * Encodeur de chaîne.
     *
     * @link http://fr2.php.net/manual/fr/function.md5.php Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * @param string $plainText
     * @param string $password
     * @param int $ivLen
     * @return string
     */
    public static function &md5Encrypt(string $plainText,
                                       string $password,
                                       int $ivLen = 16): string
    {
        $plainText .= '\x13';
        $n = strlen($plainText);

        if ($n % 16) {
            $plainText .= str_repeat('\0',
                                     16 - ($n % 16));
        }

        $i = 0;
        $encText = self::getRandIv($ivLen);
        $iv = substr($password ^ $encText,
                     0,
                     512);

        while ($i < $n) {
            $block = substr($plainText,
                            $i,
                            16) ^ pack('H*',
                                       md5($iv));
            $encText .= $block;
            $iv = substr($block . $iv,
                         0,
                         512) ^ $password;
            $i += 16;
        }
        $encText = base64_encode($encText);
        return $encText;
    }

    /**
     * Décodeur de chaîne.
     *
     * @link http://fr2.php.net/manual/fr/function.md5.php Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * @param string $encText
     * @param string $password
     * @param int $ivLen
     * @return string
     */
    public static function &md5Decrypt(string $encText,
                                       string $password,
                                       int $ivLen = 16): string
    {
        $encText = base64_decode($encText);
        $n = strlen($encText);

        $i = $ivLen;
        $plainText = '';
        $iv = substr($password ^ substr($encText,
                                        0,
                                        $ivLen),
                                        0,
                                        512);

        while ($i < $n) {
            $block = substr($encText,
                            $i,
                            16);
            $plainText .= $block ^ pack('H*',
                                        md5($iv));
            $iv = substr($block . $iv,
                         0,
                         512) ^ $password;
            $i += 16;
        }
        $plainText = preg_replace('/\\x13\\x00*$/',
                                  '',
                                  $plainText);
        return $plainText;
    }

    /**
     * Création d'une clé générée aléatoirement.
     *
     * @param int $size taille de la clé
     * @param bool $letter
     * @param bool $number
     * @param bool $caseSensitive
     * @return string
     */
    private static function &makeNewKey(int $size = 32,
                                        bool $letter = true,
                                        bool $number = true,
                                        bool $caseSensitive = true): string
    {
        $randKey = '';
        $string = '';
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        if ($letter && $number) {
            $string = $letters . $numbers;
        } else if ($letter) {
            $string = $letters;
        } else {
            $string = $numbers;
        }

        // Initialisation
        srand(time());

        for ($i = 0; $i < $size; $i++) {
            $key = substr($string,
                          (rand() % (strlen($string))),
                                            1);

            if ($caseSensitive) {
                $key = (rand(0,
                             1) == 1) ? strtoupper($key) : strtolower($key);
            }
            $randKey .= $key;
        }
        return $randKey;
    }

    /**
     * Chiffrement MD5 classique sans clé additionnelle.
     * Chiffrement de faible qualité.
     *
     * @param string $data
     * @return string
     */
    private static function &cryptByMd5Unsafe(string $data): string
    {
        $cryptData = md5($data);
        return $cryptData;
    }

    /**
     * Génère une valeur.
     *
     *
     * @link http://fr2.php.net/manual/fr/function.md5.php Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * @param int $ivLen
     * @return string
     */
    private static function &getRandIv(int $ivLen): string
    {
        $iv = '';

        while ($ivLen-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }
}