<?php

namespace PassionEngine\Engine\Block\BlockLogin;

use PassionEngine\Engine\Block\BlockModel;
use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Core\CoreCacheSection;
use PassionEngine\Engine\Core\CoreRoute;
use PassionEngine\Engine\Core\CoreMain;
use PassionEngine\Engine\Core\CoreSession;
use PassionEngine\Engine\Core\CoreSessionData;
use PassionEngine\Engine\Core\CoreLayout;
use PassionEngine\Engine\Core\CoreHtml;
use PassionEngine\Engine\Exec\ExecImage;
use PassionEngine\Engine\Lib\LibForm;
use PassionEngine\Engine\Lib\LibMakeStyle;

/**
 * Block Login.
 * Panneau d'accès rapide avec son compte.
 *
 * @author Sébastien Villemain
 */
class BlockIndex extends BlockModel
{

    private const LOCAL_VIEW_REGISTRATION = 'registration';
    private const LOCAL_VIEW_LOGON = 'logon';
    private const LOCAL_VIEW_FORGET_LOGIN = 'forgetlogin';
    private const LOCAL_VIEW_FORGET_PASS = 'forgetpass';

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
     * Affiche les icônes rapides.
     *
     * @var bool
     */
    private $displayIcons = false;

    public function display(string $view): void
    {
        $this->configure();
        $currentRoute = CoreMain::getInstance()->getRoute();
        $contentOnly = $currentRoute->isBlockLayout() && !empty($view);

        if ($contentOnly) {
            echo $this->renderContent();
        } else {
            $this->renderTemplate();
        }
    }

    public function uninstall(): void
    {
        $coreCache = CoreCache::getInstance(CoreCacheSection::FORMS);
        $coreCache->removeCache('login-logonblock.php');
        $coreCache->removeCache('login-forgetloginblock.php');
        $coreCache->removeCache('login-forgetpassblock.php');
        $coreCache->removeCache('login-registrationblock.php');
    }

    public function getViewList(): array
    {
        return array(self::LOCAL_VIEW_REGISTRATION, self::LOCAL_VIEW_LOGON, self::LOCAL_VIEW_FORGET_LOGIN, self::LOCAL_VIEW_FORGET_PASS);
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
    }

    private function renderTemplate(): void
    {
        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString('blockTitle',
                                    $this->getBlockData()
                ->getTitle());
        $libMakeStyle->assignString('blockContent',
                                    $this->renderContent());
        $libMakeStyle->display($this->getBlockData()
                ->getTemplateName());
    }

    private function &renderContent(): string
    {
        $content = '';

        if (CoreSession::connected()) {
            $content .= $this->getUserInfos();
        } else {
            $content .= $this->getGeneralPane();
        }
        return $content;
    }

    private function &getUserInfos(): string
    {
        $content = '';
        $sessionData = CoreSession::getInstance()->getSessionData();

        if ($this->displayText) {
            $content .= $this->getUserName($sessionData);
        }

        if ($this->displayAvatar && !empty($sessionData->getAvatar())) {
            $content .= $this->getUserAvatar($sessionData);
        }

        if ($this->displayIcons) {
            $content .= $this->getUserIcons();
        }
        return $content;
    }

    private function &getUserName(CoreSessionData $sessionData): string
    {
        $content = WELCOME . ' <span class=\'text_bold\'>' . $sessionData->getName() . '</span> !<br />';
        return $content;
    }

    private function &getUserAvatar(CoreSessionData $sessionData): string
    {
        $content = CoreRoute::getNewRoute()
                ->setModule('connect')
                ->setView('account')
                ->getLink(ExecImage::getTag($sessionData->getAvatar(),
                                            80))
            . '<br />';
        return $content;
    }

    private function &getUserIcons(): string
    {

        $route = CoreRoute::getNewRoute()->setModule('connect');
        $content = $route->setView('logout')->getLink(BLOCKLOGIN_LOGOUT)
            . '<br />'
            . $route->setView('account')->getLink(BLOCKLOGIN_MY_ACCOUNT)
            . '<br />'
            . $route->setModule('receiptbox')->getLink(BLOCKLOGIN_MY_RECEIPTBOX . ' (?)')
            . '<br />';
        return $content;
    }

    private function &getGeneralPane(): string
    {
        $redirectionLinks = $this->getRedirectionLinks();
        $content = '<div id=\'login-logonblock\'>'
            . $this->getLocalViewForm($redirectionLinks)
            . '</div>';
        return $content;
    }

    private function &getRedirectionLinks(): string
    {
        $moreLink = '<ul>';
        $route = CoreRoute::getNewRoute()
            ->setModule('connect')->setJsMode(true,
                                              '#login-logonblock')
            ->setBlockData($this->getBlockData());

        if (CoreMain::getInstance()->getConfigs()->registrationAllowed()) {
            $moreLink .= '<li><span class=\'text_bold\'>'
                . $route->setView('registration')->getLink(BLOCKLOGIN_GET_ACCOUNT)
                . '</span></li>';
        }

        $moreLink .= '<li>'
            . $route->setView('logon')->getLink(BLOCKLOGIN_GET_LOGON)
            . '</li>' . '<li>'
            . $route->setView('forgetlogin')->getLink(BLOCKLOGIN_GET_FORGET_LOGIN)
            . '</li>' . '<li>'
            . $route->setView('forgetpass')->getLink(BLOCKLOGIN_GET_FORGET_PASS)
            . '</li></ul>';
        return $moreLink;
    }

    private function &getLocalViewForm(string $redirectionLinks): string
    {
        $content = '';

        switch ($this->getBlockData()->getView()) {
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
        $form = new LibForm('login-logonblock');
        $form->addInputText('login',
                            LOGIN,
                            '',
                            'maxlength=\'180\'');
        $form->addInputPassword('password',
                                PASSWORD,
                                'maxlength=\'180\'');
        $form->addInputHiddenReferer();
        $form->addInputHiddenModule('connect');
        $form->addInputHiddenView('logon');
        $form->addInputHiddenLayout(CoreLayout::MODULE);
        $form->addInputSubmit('submit',
                              BLOCKLOGIN_GET_LOGON);
        $form->addHtmlInFieldset($redirectionLinks);
        CoreHtml::getInstance()->addJavascript('validLogon(\'#form-login-logonblock\', \'#form-login-logonblock-login-input\', \'#form-login-logonblock-password-input\');');
        return $form->render('login-logonblock');
    }

    /**
     * Formulaire pour un identifiant oublié.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getForgetLoginForm(string $redirectionLinks): string
    {
        $form = new LibForm('login-forgetloginblock');
        $form->addInputText('email',
                            EMAIL . ' ');
        $form->addInputHiddenModule('connect');
        $form->addInputHiddenView('forgetlogin');
        $form->addInputHiddenLayout(CoreLayout::MODULE);
        $form->addInputSubmit('submit',
                              VALID);
        $form->addHtmlInFieldset($redirectionLinks);
        CoreHtml::getInstance()->addJavascript('validForgetLogin(\'#form-login-forgetloginblock\', \'#form-login-forgetloginblock-email-input\');');
        return $form->render('login-forgetloginblock');
    }

    /**
     * Formulaire de mot de passe oublié.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getForgetPassForm(string $redirectionLinks): string
    {
        $form = new LibForm('login-forgetpassblock');
        $form->addInputText('login',
                            LOGIN . ' ');
        $form->addInputHiddenModule('connect');
        $form->addInputHiddenView('forgetpass');
        $form->addInputHiddenLayout(CoreLayout::MODULE);
        $form->addInputSubmit('submit',
                              VALID);
        $form->addHtmlInFieldset($redirectionLinks);
        CoreHtml::getInstance()->addJavascript('validForgetPass(\'#form-login-forgetpassblock\', \'#form-login-forgetpassblock-login-input\');');
        return $form->render('login-forgetpassblock');
    }

    /**
     * Formulaire d'enregistrement.
     *
     * @param string $redirectionLinks
     * @return string
     */
    private function &getRegistrationForm(string $redirectionLinks): string
    { // TODO registration block a coder
        $form = new LibForm('login-registrationblock');
        $form->addInputText('login',
                            LOGIN . ' ');
        $form->addInputHiddenModule('connect');
        $form->addInputHiddenview('registration');
        // $form->addInputHidden('layout', 'module');
        $form->addInputSubmit('submit',
                              VALID);
        $form->addHtmlInFieldset($redirectionLinks);
        // CoreHtml::getInstance()->addJavascript('validForgetPass('#form-login-registrationblock', '#form-login-registrationblock-login-input');');
        return $form->render('login-registrationblock');
    }
}