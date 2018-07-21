<?php

namespace TREngine\Engine\Block\BlockImageGenerator;

use TREngine\Engine\Block\BlockModel;
use TREngine\Engine\Fail\FailBlock;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreRequestType;
use TREngine\Engine\Core\CoreSecure;

/**
 * Block permettant de générer une image.
 *
 * @author Sébastien Villemain
 */
class BlockImageGenerator extends BlockModel
{

    public function display()
    {
        $mode = null;

        if (CoreMain::getInstance()->getCurrentRoute()->isBlockLayout()) {
            $mode = CoreRequest::getString("mode",
                                           "",
                                           CoreRequestType::GET);
        }

        // Type d'affichage demandé
        switch ($mode) {
            case "code":
                $this->renderCode();
                break;
            case "text":
                $this->renderText();
                break;
            default:
                CoreSecure::getInstance()->catchException(new FailBlock("invalid image mode",
                                                                        23,
                                                                        array($mode)));
                break;
        }
    }

    public function install()
    {

    }

    public function uninstall()
    {

    }

    /**
     * Methode captcha code.
     */
    private function renderCode()
    {
        $code = CoreRequest::getString("code",
                                       "",
                                       CoreRequestType::GET);

        if (empty($code)) {
            CoreSecure::getInstance()->catchException(new FailBlock("invalid captcha code",
                                                                    23,
                                                                    array($code)));
        }

        // Cryptage
        $text = md5($code);
        $text = substr($text,
                       2,
                       5);

        header("Content-type: image/png");
        $im = imagecreatefromjpeg("Resources/ImageGenerator/captcha.jpg");
        $id = imagecreatefromjpeg("Resources/ImageGenerator/captcha.jpg");
        $grey = imagecolorallocate($im,
                                   128,
                                   128,
                                   128);
        $black = imagecolorallocate($im,
                                    0,
                                    0,
                                    0);
        $font = "Resources/ImageGenerator/Alanden_.ttf";

        for ($i = 0; $i < 5; $i++) {
            $angle = mt_rand(10,
                             30);

            if (mt_rand(0,
                        1) == 1) {
                $angle = - $angle;
            }

            imagettftext($im,
                         12,
                         $angle,
                         11 + (20 * $i),
                         15,
                         $grey,
                         $font,
                         substr($text,
                                $i,
                                1));
            imagettftext($im,
                         12,
                         $angle,
                         10 + (20 * $i),
                         16,
                         $black,
                         $font,
                         substr($text,
                                $i,
                                1));
        }

        imagecopymerge($im,
                       $id,
                       0,
                       0,
                       0,
                       0,
                       120,
                       30,
                       50);
        imagepng($im);
        imagedestroy($im);
        imagedestroy($id);
    }

    /**
     * Methode text & email.
     */
    private function renderText()
    {
        $text = CoreRequest::getString("text",
                                       "",
                                       CoreRequestType::GET);

        if (empty($text)) {
            CoreSecure::getInstance()->catchException(new FailBlock("invalid captcha text",
                                                                    23,
                                                                    array($text)));
        }

        // Décryptage
        $text = urldecode($text);
        $text = base64_decode($text);

        if (preg_match("/_AT_/",
                       $text)) {
            $text = str_replace("_AT_",
                                "@",
                                $text);
            $text = str_replace("_POINT_",
                                ".",
                                $text);
        }

        header("Content-type: image/png");

        if (strlen($text) < 5) {
            $morePixel = 5;
        } else if (strlen($text) > 100) {
            $morePixel = -((strlen($text) - 60) / 1.8);
        } else if (strlen($text) > 60) {
            $morePixel = -((strlen($text) - 60));
        } else if (strlen($text) > 50) {
            $morePixel = -((strlen($text) - 50));
        } else {
            $morePixel = 0;
        }

        $textSize = 6;
        $width = (strlen($text) * ($textSize / 1.05)) + $morePixel + 2;
        $image = imagecreate($width,
                             13);
        $font = imagecolorallocate($image,
                                   106,
                                   115,
                                   124);
        $textColor = imagecolorallocate($image,
                                        5,
                                        88,
                                        123); // couleur eclat bleue / blue color
        // $textColor = imagecolorallocate($image, 128, 255, 169); // couleur bleue / real blue color
        // $textColor = imagecolorallocate($image, 0, 0, 0); // couleur noir / black color
        // $textColor = imagecolorallocate($image, 255, 255, 255); // couleur blanc / white color
        // $textColor = imagecolorallocate($image, 128, 255, 255); // couleur rouge / red color
        // $textColor = imagecolorallocate($image, 85, 255, 128); // couleur verte / green color

        imagettftext($image,
                     $textSize,
                     0,
                     2,
                     12,
                     $textColor,
                     "Resources/ImageGenerator/nokiafc22.ttf",
                     $text);
        imagecolortransparent($image,
                              $font); // implication de la transparence
        imagepng($image);
        imagedestroy($image);
    }
}