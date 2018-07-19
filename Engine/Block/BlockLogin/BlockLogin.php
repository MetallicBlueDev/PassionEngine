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
use TREngine\Engine\Core\CoreLayout;
use TREngine\Engine\Exec\ExecImage;
use TREngine\Engine\Lib\LibForm;
use TREngine\Engine\Lib\LibMakeStyle;

/**
 * Block login.
 * Panneau d'accès rapide avec son compte.
 *
 * @author Sébastien Villemain
 */
class BlockLogin extends BlockModel
{

    private const LOCAL_VIEW_REGISTRATION = "registration";
    private const LOCAL_VIEW_LOGON = "logon";
    private const LOCAL_VIEW_FORGET_LOGIN = "forgetlogin";
    private const LOCAL_VIEW_FORGET_PASS = "forgetpass";

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

    public function display(): void
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

    public function uninstall(): void
    {
        $coreCache = CoreCache::getInstance(CoreCacheSection::FORMS);
        $coreCache->removeCache("login-logonblock.php");
        $coreCache->removeCache("login-forgetloginblock.php");
        $coreCache->removeCache("login-forgetpassblock.php");
        $coreCache->removeCache("login-registrationblock.php");
    }

    private function configure(): void
    {
        $options = $this->getBlockData()->getConfigs();

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

        if (CoreMain::getInstance()->isBlockLayout()) {
            // Si nous sommes dans un affichage type block
            $this->localView = CoreRequest::getString("localView",
                                                      "",
                                                      CoreRequestType::GET);
        }
    }

    private function &render(): string
    {
        $content = "";

        if (CoreSession::connected()) {
            $content .= $this->getUserInfos();
        } else {
            $content .= $this->getGeneralPane();
        }
        return $content;
    }

    private function &getUserInfos(): string
    {
        $content = "";
        $userInfos = CoreSession::getInstance()->getUserInfos();

        if ($this->displayText) {
            $content .= WELCOME . " <span class=\"text_bold\">" . $userInfos->getName() . "</span> !<br />";
        }

        if ($this->displayAvatar && !empty($userInfos->getAvatar())) {
            $content .= CoreHtml::getLinkForModule("connect",
                                                "account",
                                                ExecImage::getTag($userInfos->getAvatar(),
                                                                  80))
                . "<br />";
        }

        if ($this->displayIcons) {
            $content .= CoreHtml::getLinkForModule("connect",
                                                "logout",
                                                BLOCKLOGIN_LOGOUT)
                . "<br />"
                . CoreHtml::getLinkForModule("connect",
                                          "account",
                                          BLOCKLOGIN_MY_ACCOUNT)
                . "<br />"
                . CoreHtml::getLinkForModule("receiptbox",
                                          "",
                                          BLOCKLOGIN_MY_RECEIPTBOX . " (?)")
                . "<br />";
        }
        return $content;
    }

    private function &getGeneralPane(): string
    {
        $redirectionLinks = $this->getRedirectionLinks();
        $content = "<div id=\"login-logonblock\">"
            . $this->getLocalViewForm($redirectionLinks)
            . "</div>";
        return $content;
    }

    private function &getRedirectionLinks(): string
    {
        $moreLink = "<ul>";

        if (CoreMain::getInstance()->getConfigs()->registrationAllowed()) {
            $moreLink .= "<li><span class=\"text_bold\">"
                . CoreHtml::getLinkWithAjax(CoreLayout::REQUEST_MODULE . "=connect&" . CoreLayout::REQUEST_VIEW . "=registration",
                                            CoreLayout::REQUEST_BLOCKTYPE . "=" . $this->getBlockData()->getType() . "&localView=" . self::LOCAL_VIEW_REGISTRATION,
                                            "#login-logonblock",
                                            BLOCKLOGIN_GET_ACCOUNT)
                . "</span></li>";
        }

        $moreLink .= "<li>"
            . CoreHtml::getLinkWithAjax(CoreLayout::REQUEST_MODULE . "=connect&" . CoreLayout::REQUEST_VIEW . "=logon",
                                        CoreLayout::REQUEST_BLOCKTYPE . "=" . $this->getBlockData()->getType() . "&localView=" . self::LOCAL_VIEW_LOGON,
                                        "#login-logonblock",
                                        BLOCKLOGIN_GET_LOGON)
            . "</li>" . "<li>"
            . CoreHtml::getLinkWithAjax(CoreLayout::REQUEST_MODULE . "=connect&" . CoreLayout::REQUEST_VIEW . "=forgetlogin",
                                        CoreLayout::REQUEST_BLOCKTYPE . "=" . $this->getBlockData()->getType() . "&localView=" . self::LOCAL_VIEW_FORGET_LOGIN,
                                        "#login-logonblock",
                                        BLOCKLOGIN_GET_FORGET_LOGIN)
            . "</li>" . "<li>"
            . CoreHtml::getLinkWithAjax(CoreLayout::REQUEST_MODULE . "=connect&" . CoreLayout::REQUEST_VIEW . "=forgetpass",
                                        CoreLayout::REQUEST_BLOCKTYPE . "=" . $this->getBlockData()->getType() . "&localView=" . self::LOCAL_VIEW_FORGET_PASS,
                                        "#login-logonblock",
                                        BLOCKLOGIN_GET_FORGET_PASS)
            . "</li></ul>";
        return $moreLink;
    }

    private function &getLocalViewForm(string $redirectionLinks): string
    {
        $content = "";

        switch ($this->localView) {
            case self::LOCAL_VIEW_LOGON:
                $content .= $this->getLogonForm($redirectionLinks);
                break;
            case self::LOCAL_VIEW_FORGET_LOGIN:
                $content .= $this->getForgetLoginForm($redirectionLinks);
                break;
            case self::LOCAL_VIEW_FORGET_PASS:
                $content .= $this->getForgetPassForm($redirectionLinks);
                break;
            case self::LOCAL_VIEW_REGISTRATION:
                $content .= $this->getRegistrationForm($redirectionLinks);
                break;
            default:
                $content .= $this->getLogonForm($redirectionLinks);
                break;
        }
        return $content;
    }

    /**
     * Formulaire de connexion.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getLogonForm(string $redirectionLinks): string
    {
        $form = new LibForm("login-logonblock");
        $form->addInputText("login",
                            LOGIN,
                            "",
                            "maxlength=\"180\"");
        $form->addInputPassword("password",
                                PASSWORD,
                                "maxlength=\"180\"");
        $form->addInputHiddenReferer();
        $form->addInputHiddenModule("connect");
        $form->addInputHiddenView("logon");
        $form->addInputHiddenLayout(CoreLayout::MODULE);
        $form->addInputSubmit("submit",
                              BLOCKLOGIN_GET_LOGON);
        $form->addHtmlInFieldset($redirectionLinks);
        CoreHtml::getInstance()->addJavascript("validLogon('#form-login-logonblock', '#form-login-logonblock-login-input', '#form-login-logonblock-password-input');");
        return $form->render("login-logonblock");
    }

    /**
     * Formulaire d'identifiant oublié.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getForgetLoginForm(string $redirectionLinks): string
    {
        $form = new LibForm("login-forgetloginblock");
        $form->addInputText("email",
                            EMAIL . " ");
        $form->addInputHiddenModule("connect");
        $form->addInputHiddenView("forgetlogin");
        $form->addInputHiddenLayout(CoreLayout::MODULE);
        $form->addInputSubmit("submit",
                              VALID);
        $form->addHtmlInFieldset($redirectionLinks);
        CoreHtml::getInstance()->addJavascript("validForgetLogin('#form-login-forgetloginblock', '#form-login-forgetloginblock-email-input');");
        return $form->render("login-forgetloginblock");
    }

    /**
     * Formulaire de mot de passe oublié.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getForgetPassForm(string $redirectionLinks): string
    {
        $form = new LibForm("login-forgetpassblock");
        $form->addInputText("login",
                            LOGIN . " ");
        $form->addInputHiddenModule("connect");
        $form->addInputHiddenView("forgetpass");
        $form->addInputHiddenLayout(CoreLayout::MODULE);
        $form->addInputSubmit("submit",
                              VALID);
        $form->addHtmlInFieldset($redirectionLinks);
        CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-forgetpassblock', '#form-login-forgetpassblock-login-input');");
        return $form->render("login-forgetpassblock");
    }

    /**
     * Formulaire d'enregistrement.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getRegistrationForm(string $redirectionLinks): string
    { // TODO registration block a coder
        $form = new LibForm("login-registrationblock");
        $form->addInputText("login",
                            LOGIN . " ");
        $form->addInputHiddenModule("connect");
        $form->addInputHiddenview("registration");
        // $form->addInputHidden("layout", "module");
        $form->addInputSubmit("submit",
                              VALID);
        $form->addHtmlInFieldset($redirectionLinks);
        // CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-registrationblock', '#form-login-registrationblock-login-input');");
        return $form->render("login-registrationblock");
    }
}