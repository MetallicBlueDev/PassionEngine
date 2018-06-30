<?php

namespace TREngine\Engine\Block\BlockLogin;

use TREngine\Engine\Block\BlockModel;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreRequestType;
use TREngine\Engine\Core\CoreSession;
use TREngine\Engine\Exec\ExecImage;
use TREngine\Engine\Lib\LibForm;
use TREngine\Engine\Lib\LibMakeStyle;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block login, accès rapide à une connexion, à une déconnexion et à son compte.
 *
 * @author Sébastien Villemain
 */
class BlockLogin extends BlockModel
{

    /**
     * Affiche le texte de bienvenue.
     *
     * @var bool
     */
    private $displayText = false;

    /**
     * Affiche l'avatar.
     *
     * @var bool
     */
    private $displayAvatar = false;

    /**
     * Affiche les icons rapides.
     *
     * @var bool
     */
    private $displayIcons = false;

    /**
     * Type d'affichage demandé.
     *
     * @var string
     */
    private $localView = "";

    public function display()
    {
        $this->configure();

        if (!empty($this->localView)) {
            echo $this->render();
        } else {
            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assignString("blockTitle",
                                        $this->getBlockData()
                            ->getTitle());
            $libMakeStyle->assignString("blockContent",
                                        $this->render());
            $libMakeStyle->display($this->getBlockData()
                            ->getTemplateName());
        }
    }

    public function install()
    {

    }

    public function uninstall()
    {
        $coreCache = CoreCache::getInstance(CoreCacheSection::FORMS);
        $coreCache->removeCache("login-logonblock.php");
        $coreCache->removeCache("login-forgetloginblock.php");
        $coreCache->removeCache("login-forgetpassblock.php");
        $coreCache->removeCache("login-registrationblock.php");
    }

    private function configure()
    {
        $options = explode('|',
                           $this->getBlockData()->getContent());

        foreach ($options as $key => $value) {
            switch ($key) {
                case 0:
                    $this->displayText = ($value == 1) ? true : false;
                    break;
                case 1:
                    $this->displayAvatar = ($value == 1) ? true : false;
                    break;
                case 2:
                    $this->displayIcons = ($value == 1) ? true : false;
                    break;
            }
        }

        if (CoreMain::getInstance()->isBlockLayout()) { // Si nous sommes dans un affichage type block
            $this->localView = CoreRequest::getString("localView",
                                                      "",
                                                      CoreRequestType::GET);
        }
    }

    private function &render()
    {
        $content = "";

        if (CoreSession::connected()) {
            $userInfos = CoreSession::getInstance()->getUserInfos();

            if ($this->displayText) {
                $content .= WELCOME . " <span class=\"text_bold\">" . $userInfos->getName() . "</span> !<br />";
            }

            if ($this->displayAvatar && !empty($userInfos->getAvatar())) {
                $content .= CoreHtml::getLink("module=connect&view=account",
                                              ExecImage::resize($userInfos->getAvatar(),
                                                                80)) . "<br />";
            }

            if ($this->displayIcons) {
                $content .= CoreHtml::getLink("module=connect&view=logout",
                                              BLOCKLOGIN_LOGOUT) . "<br />" . CoreHtml::getLink("module=connect&view=account",
                                                                                                BLOCKLOGIN_MY_ACCOUNT) . "<br />" . CoreHtml::getLink("module=receiptbox",
                                                                                                                                                      BLOCKLOGIN_MY_RECEIPTBOX . " (?)") . "<br />";
            }
        } else {
            $moreLink = "<ul>";

            if (CoreMain::getInstance()->getConfigs()->registrationAllowed()) {
                $moreLink .= "<li><span class=\"text_bold\">" . CoreHtml::getLinkWithAjax("module=connect&view=registration",
                                                                                          "blockType=" . $this->getBlockData()->getType() . "&localView=registration",
                                                                                          "#login-logonblock",
                                                                                          BLOCKLOGIN_GET_ACCOUNT) . "</span></li>";
            }

            $moreLink .= "<li>" . CoreHtml::getLinkWithAjax("module=connect&view=logon",
                                                            "blockType=" . $this->getBlockData()->getType() . "&localView=logon",
                                                            "#login-logonblock",
                                                            BLOCKLOGIN_GET_LOGON) . "</li>" . "<li>" . CoreHtml::getLinkWithAjax("module=connect&view=forgetlogin",
                                                                                                                                 "blockType=" . $this->getBlockData()->getType() . "&localView=forgetlogin",
                                                                                                                                 "#login-logonblock",
                                                                                                                                 BLOCKLOGIN_GET_FORGET_LOGIN) . "</li>" . "<li>" . CoreHtml::getLinkWithAjax("module=connect&view=forgetpass",
                                                                                                                                                                                                             "blockType=" . $this->getBlockData()->getType() . "&localView=forgetpass",
                                                                                                                                                                                                             "#login-logonblock",
                                                                                                                                                                                                             BLOCKLOGIN_GET_FORGET_PASS) . "</li></ul>";

            $content .= "<div id=\"login-logonblock\">";

            switch ($this->localView) {
                case 'logon':
                    $content .= $this->logon($moreLink);
                    break;
                case 'forgetlogin':
                    $content .= $this->forgetlogin($moreLink);
                    break;
                case 'forgetpass':
                    $content .= $this->forgetpass($moreLink);
                    break;
                case 'registration':
                    $content .= $this->registration($moreLink);
                    break;
                default:
                    $content .= $this->logon($moreLink);
                    break;
            }
            $content .= "</div>";
        }
        return $content;
    }

    /**
     * Formulaire de connexion.
     *
     * @param $moreLink string
     * @return string
     */
    private function &logon($moreLink)
    {
        $form = new LibForm("login-logonblock");
        $form->addInputText("login",
                            LOGIN,
                            "",
                            "maxlength=\"180\"");
        $form->addInputPassword("password",
                                PASSWORD,
                                "maxlength=\"180\"");
        $form->addInputHidden("referer",
                              CoreRequest::getRefererQueryString());
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "logon");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputSubmit("submit",
                              BLOCKLOGIN_GET_LOGON);
        $form->addHtmlInFieldset($moreLink);
        CoreHtml::getInstance()->addJavascript("validLogon('#form-login-logonblock', '#form-login-logonblock-login-input', '#form-login-logonblock-password-input');");
        return $form->render("login-logonblock");
    }

    /**
     * Formulaire de login oubli�.
     *
     * @param $moreLink string
     * @return string
     */
    private function &forgetlogin($moreLink)
    {
        $form = new LibForm("login-forgetloginblock");
        $form->addInputText("mail",
                            MAIL . " ");
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "forgetlogin");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputSubmit("submit",
                              VALID);
        $form->addHtmlInFieldset($moreLink);
        CoreHtml::getInstance()->addJavascript("validForgetLogin('#form-login-forgetloginblock', '#form-login-forgetloginblock-mail-input');");
        return $form->render("login-forgetloginblock");
    }

    /**
     * Formulaire de mot de passe oubli�.
     *
     * @param $moreLink string
     * @return string
     */
    private function &forgetpass($moreLink)
    {
        $form = new LibForm("login-forgetpassblock");
        $form->addInputText("login",
                            LOGIN . " ");
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "forgetpass");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputSubmit("submit",
                              VALID);
        $form->addHtmlInFieldset($moreLink);
        CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-forgetpassblock', '#form-login-forgetpassblock-login-input');");
        return $form->render("login-forgetpassblock");
    }

    private function &registration($moreLink)
    { // TODO registration block a coder
        $form = new LibForm("login-registrationblock");
        $form->addInputText("login",
                            LOGIN . " ");
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "registration");
        // $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit",
                              VALID);
        $form->addHtmlInFieldset($moreLink);
        // CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-registrationblock', '#form-login-registrationblock-login-input');");
        return $form->render("login-registrationblock");
    }
}