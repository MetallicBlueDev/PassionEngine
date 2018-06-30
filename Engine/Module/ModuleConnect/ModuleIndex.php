<?php

namespace TREngine\Engine\Module\ModuleConnect;

use TREngine\Engine\Module\ModuleModel;
use TREngine\Engine\Core\CoreSession;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreRequestType;
use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Lib\LibTabs;
use TREngine\Engine\Lib\LibForm;
use TREngine\Engine\Exec\ExecUrl;
use TREngine\Engine\Exec\ExecString;
use TREngine\Engine\Exec\ExecMailer;

/**
 * Module d'identification.
 *
 * @author Sébastien Villemain
 */
class ModuleIndex extends ModuleModel
{

    public function display()
    {
        if (CoreSession::connected()) {
            $this->account();
        } else {
            $this->logon();
        }
    }

    public function account()
    {
        if (CoreSession::connected()) {
            // Ajout des formulaires dans les onglets
            $accountTabs = new LibTabs("accounttabs");
            $accountTabs->addTab(ACCOUNT_PROFILE,
                                 $this->tabProfile());
            $accountTabs->addTab(ACCOUNT_PRIVATE,
                                 $this->tabAccount());
            $accountTabs->addTab(ACCOUNT_AVATAR,
                                 $this->tabAvatar());

            if (CoreSession::getInstance()->getUserInfos()->hasAdminRank()) {
                $accountTabs->addTab(ACCOUNT_ADMIN,
                                     $this->tabAdmin());
            }
            echo $accountTabs->render();
        } else {
            $this->display();
        }
    }

    private function tabProfile(): string
    {
        $form = new LibForm("account-profile");
        $form->setTitle(ACCOUNT_PROFILE_TITLE);
        $form->setDescription(ACCOUNT_PROFILE_DESCRIPTION);
        $form->addSpace();
        $form->addInputText("website",
                            ACCOUNT_PROFILE_WEBSITE);
        $form->addTextarea("signature",
                           ACCOUNT_PROFILE_SIGNATURE,
                           CoreSession::getInstance()->getUserInfos()->getSignature(),
                           "style=\"display: block;\" rows=\"5\" cols=\"50\"");
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "sendProfile");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputSubmit("submit",
                              VALID);
        return $form->render();
    }

    public function sendProfile()
    {
        $values = array();
        $website = CoreRequest::getString("website",
                                          "",
                                          CoreRequestType::POST);
        $signature = CoreRequest::getString("signature",
                                            "",
                                            CoreRequestType::POST);

        if (!empty($website)) {
            $values['website'] = ExecUrl::cleanUrl($website);
        }
        if (!empty($signature)) {
            $values['signature'] = $signature;
        }

        if (!empty($values)) {
            $coreSql = CoreSql::getInstance();
            $coreSession = CoreSession::getInstance();

            $coreSql->update(
                    CoreTable::USERS,
                    $values,
                    array(
                "user_id = '" . $coreSession->getUserInfos()->getId() . "'")
            );

            if ($coreSql->affectedRows() > 0) {
                $coreSession->refreshSession();
                CoreLogger::addInfo(DATA_SAVED);
            }
        }
        if (CoreMain::getInstance()->isDefaultLayout()) {
            CoreHtml::getInstance()->redirect("index.php?module=connect&view=account&selectedTab=accounttabsidTab0",
                                              1);
        }
    }

    private function tabAccount(): string
    {
        $userInfos = CoreSession::getInstance()->getUserInfos();

        $form = new LibForm("account-accountprivate");
        $form->setTitle(ACCOUNT_PRIVATE_TITLE);
        $form->setDescription(ACCOUNT_PRIVATE_DESCRIPTION);
        $form->addSpace();
        $form->addInputText("name",
                            LOGIN,
                            $userInfos->getName());
        $form->addInputPassword("pass",
                                PASSWORD);
        $form->addInputPassword("pass2",
                                ACCOUNT_PRIVATE_PASSWORD_CONFIRME);
        $form->addInputText("mail",
                            MAIL,
                            $userInfos->getMail());

        $form->addSpace();
        $form->addSelectOpenTag("langue",
                                ACCOUNT_PRIVATE_LANGUE);

        $langues = CoreTranslate::getLangList();
        $currentLanguage = CoreTranslate::getInstance()->getCurrentLanguage();
        $form->addSelectItemTag($currentLanguage,
                                "",
                                true);

        // Liste des langages disponibles
        foreach ($langues as $langue) {
            if ($langue == $currentLanguage) {
                continue;
            }
            $form->addSelectItemTag($langue);
        }

        $form->addSelectCloseTag();
        $form->addSelectOpenTag("template",
                                ACCOUNT_PRIVATE_TEMPLATE)
        ;
        $templates = LibMakeStyle::getTemplateList();
        $currentTemplate = LibMakeStyle::getTemplateDir();
        $form->addSelectItemTag($currentTemplate,
                                "",
                                true);

        // Liste des templates disponibles
        foreach ($templates as $template) {
            if ($template == $currentTemplate) {
                continue;
            }
            $form->addSelectItemTag($template);
        }
        $form->addSelectCloseTag();
        $form->addSpace();
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "sendAccount");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputSubmit("submit",
                              VALID);
        CoreHtml::getInstance()->addJavascript("validAccount('#form-account-accountprivate', '#form-account-accountprivate-name-input', '#form-account-accountprivate-pass-input', '#form-account-accountprivate-pass2-input', '#form-account-accountprivate-mail-input');");
        return $form->render();
    }

    public function sendAccount()
    {
        $userInfos = CoreSession::getInstance()->getUserInfos();

        $name = CoreRequest::getWord("name",
                                     "",
                                     CoreRequestType::POST);
        $pass = CoreRequest::getString("pass",
                                       "",
                                       CoreRequestType::POST);
        $pass2 = CoreRequest::getString("pass2",
                                        "",
                                        CoreRequestType::POST);
        $mail = CoreRequest::getString("mail",
                                       "",
                                       CoreRequestType::POST);
        $langue = CoreRequest::getString("langue",
                                         "",
                                         CoreRequestType::POST);
        $template = CoreRequest::getString("template",
                                           "",
                                           CoreRequestType::POST);

        if ($userInfos->getName() != $name || $userInfos->getMail() != $mail || $userInfos->getLangue() != $langue || $userInfos->getTemplate() != $template) {
            if (CoreSession::validLogin($name)) {
                $validName = true;

                if ($userInfos->getName() != $name) {
                    $name = ExecString::secureText($name);

                    CoreSql::getInstance()->select(
                            CoreTable::USERS,
                            array(
                        "user_id"),
                            array(
                        "name = '" . $name . "'")
                    );

                    if (CoreSql::getInstance()->affectedRows() > 0) {
                        $validName = false;
                        CoreLogger::addWarning(ACCOUNT_PRIVATE_LOGIN_IS_ALLOWED);
                    }
                }
                if ($validName) {
                    if (ExecMailer::isValidMail($mail)) {
                        $values = array();
                        if (!empty($pass) || !empty($pass2)) {
                            if ($pass == $pass2) {
                                if (CoreSession::validPassword($pass)) {
                                    $values['pass'] = CoreSession::cryptPass($pass);
                                } else {
                                    $this->errorBox();
                                }
                            } else {
                                CoreLogger::addWarning(ACCOUNT_PRIVATE_PASSWORD_INVALID_CONFIRME);
                            }
                        }
                        $values['name'] = $name;
                        $values['mail'] = $mail;
                        $values['langue'] = $langue;
                        $values['template'] = $template;
                        CoreSql::getInstance()->update(
                                CoreTable::USERS,
                                $values,
                                array(
                            "user_id = '" . $userInfos->getId() . "'")
                        );

                        if (CoreSql::getInstance()->affectedRows() > 0) {
                            CoreSession::getInstance()->refreshSession();
                            CoreLogger::addInfo(DATA_SAVED);
                        }
                    } else {
                        CoreLogger::addWarning(INVALID_MAIL);
                    }
                }
            } else {
                $this->errorBox();
            }
        }
        if (CoreMain::getInstance()->isDefaultLayout()) {
            CoreHtml::getInstance()->redirect("index.php?module=connect&view=account&selectedTab=accounttabsidTab1",
                                              1);
        }
    }

    private function tabAvatar(): string
    {
        $form = new LibForm("account-avatar");
        $form->setTitle(ACCOUNT_AVATAR_TITLE);
        $form->setDescription(ACCOUNT_AVATAR_DESCRIPTION);
        $form->addSpace();
        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "account");
        $form->addInputHidden("layout",
                              "module");
        $form->addInputSubmit("submit",
                              VALID);
        return $form->render();
    }

    private function tabAdmin(): string
    {
        $userInfos = CoreSession::getInstance()->getUserInfos();

        $form = new LibForm("account-admin");
        $form->setTitle(ACCOUNT_ADMIN_TITLE);
        $form->setDescription(ACCOUNT_ADMIN_DESCRIPTION);

        // Type de compte admin
        $form->addSpace();
        $form->addHtmlInFieldset("<span class=\"text_bold\">");

        if ($userInfos->hasSuperAdminRank()) {
            $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_MAX);
        } else if ($userInfos->hasAdminWithRightsRank()) {
            $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_HIG);
        } else {
            $form->addHtmlInFieldset(ACCOUNT_ADMIN_RIGHT_MED);
        }

        $form->addHtmlInFieldset("</span>");

        // Liste des droits
        $form->addSpace();
        $form->addHtmlInFieldset("<span class=\"text_underline\">" . ACCOUNT_ADMIN_RIGHT . ":</span>");

        if ($userInfos->hasSuperAdminRank()) {
            $form->addHtmlInFieldset(ADMIN_RIGHT_ALL);
        } else {
            foreach ($userInfos->getRights() as $userAccessType) {
                if (!$userAccessType->valid()) {
                    continue;
                }

                $text = "";

                if ($userAccessType->isModuleZone()) {
                    $text = ADMIN_RIGHT_MODULE;
                } else if ($userAccessType->isBlockZone()) {
                    $text = ADMIN_RIGHT_BLOCK;
                }

                $form->addHtmlInFieldset($text . " <span class=\"text_bold\">" . $userAccessType->getPage() . "</span> (#" . $userAccessType->getId() . ")");
            }
        }

        $form->addInputHidden("module",
                              "connect");
        $form->addInputHidden("view",
                              "account");
        $form->addInputHidden("layout",
                              "module");
        return $form->render();
    }

    /**
     * Formulaire de connexion
     */
    public function logon()
    {
        if (!CoreSession::connected()) {
            $login = CoreRequest::getString("login",
                                            "",
                                            CoreRequestType::POST);
            $password = CoreRequest::getString("password",
                                               "",
                                               CoreRequestType::POST);

            if (!empty($login) || !empty($password)) {
                if (CoreSession::openSession($login,
                                             $password)) {
                    // Redirection de la page
                    $url = "";
                    $referer = base64_decode(urldecode(CoreRequest::getString("referer",
                                                                              "",
                                                                              CoreRequestType::POST)));
                    if (!empty($referer)) {
                        $url = $referer;
                    } else {
                        $url = $this->getModuleData()->getConfigValue("defaultUrlAfterLogon",
                                                                      "module=home");
                    }
                    CoreHtml::getInstance()->redirect("index.php?" . $url);
                } else {
                    $this->errorBox();
                }
            }

            if (CoreMain::getInstance()->isDefaultLayout() || (empty($login) && empty($password))) {
                $form = new LibForm("login-logon");
                $form->setTitle(LOGIN_FORM_TITLE);
                $form->setDescription(LOGIN_FORM_DESCRIPTION);
                $form->addInputText("login",
                                    LOGIN,
                                    "",
                                    "maxlength=\"180\" value=\"" . $login . "\"");
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
                                      CONNECT);

                $moreLink = "<ul>";
                if (CoreMain::getInstance()->getConfigs()->registrationAllowed()) {
                    $moreLink .= "<li><span class=\"text_bold\">" . CoreHtml::getLink("module=connect&view=registration",
                                                                                      LINK_TO_NEW_ACCOUNT) . "</span></li>";
                }
                $moreLink .= "<li>" . CoreHtml::getLink("module=connect&view=forgetlogin",
                                                        LINK_TO_FORGET_LOGIN) . "</li>"
                        . "<li>" . CoreHtml::getLink("module=connect&view=forgetpass",
                                                     LINK_TO_FORGET_PASS) . "</li></ul>";

                $form->addHtmlInFieldset($moreLink);

                echo $form->render();
                CoreHtml::getInstance()->addJavascript("validLogon('#form-login-logon', '#form-login-logon-login-input', '#form-login-logon-password-input');");
            }
        } else {
            $this->display();
        }
    }

    /**
     * Envoie des messages d'erreurs
     */
    private function errorBox()
    {
        foreach (CoreSession::getErrorMessage() as $errorMessage) {
            CoreLogger::addWarning($errorMessage);
        }
    }

    /**
     * Déconnexion du client
     */
    public function logout()
    {
        CoreSession::closeSession();
        CoreHtml::getInstance()->redirect();
    }

    /**
     * Formulaire d'identifiant oublié
     */
    public function forgetlogin()
    {
        if (!CoreSession::connected()) {
            $login = "";
            $ok = false;
            $mail = CoreRequest::getString("mail",
                                           "",
                                           CoreRequestType::POST);

            if (!empty($mail)) {
                if (ExecMailer::isValidMail($mail)) {
                    CoreSql::getInstance()->select(
                            CoreTable::USERS,
                            array(
                        "name"),
                            array(
                        "mail = '" . $mail . "'")
                    );

                    if (CoreSql::getInstance()->affectedRows() == 1) {
                        list($login) = CoreSql::getInstance()->fetchArray();
                        $ok = ExecMailer::sendMail(); // TODO envoyer un mail
                    }
                    if (!$ok) {
                        CoreLogger::addWarning(FORGET_LOGIN_INVALID_MAIL_ACCOUNT);
                    }
                } else {
                    CoreLogger::addWarning(INVALID_MAIL);
                }
            }

            if ($ok) {
                CoreLogger::addInfo(FORGET_LOGIN_IS_SUBMIT_TO . " " . $mail);
            } else {
                if (CoreMain::getInstance()->isDefaultLayout() || empty($mail)) {
                    $form = new LibForm("login-forgetlogin");
                    $form->setTitle(FORGET_LOGIN_TITLE);
                    $form->setDescription(FORGET_LOGIN_DESCRIPTION);
                    $form->addInputText("mail",
                                        MAIL);
                    $form->addInputHidden("module",
                                          "connect");
                    $form->addInputHidden("view",
                                          "forgetlogin");
                    $form->addInputHidden("layout",
                                          "module");
                    $form->addInputSubmit("submit",
                                          FORGET_LOGIN_SUBMIT);

                    $moreLink = "<ul>";
                    if (CoreMain::getInstance()->getConfigs()->registrationAllowed()) {
                        $moreLink .= "<li><span class=\"text_bold\">" . CoreHtml::getLink("module=connect&view=registration",
                                                                                          LINK_TO_NEW_ACCOUNT) . "</span></li>";
                    }
                    $moreLink .= "<li>" . CoreHtml::getLink("module=connect&view=logon",
                                                            LINK_TO_LOGON) . "</li>"
                            . "<li>" . CoreHtml::getLink("module=connect&view=forgetpass",
                                                         LINK_TO_FORGET_PASS) . "</li></ul>";

                    $form->addHtmlInFieldset($moreLink);

                    echo $form->render();
                    CoreHtml::getInstance()->addJavascript("validForgetLogin('#form-login-forgetlogin', '#form-login-forgetlogin-mail-input');");
                }
            }
        } else {
            $this->display();
        }
    }

    /**
     * Formulaire de mot de passe oublié
     */
    public function forgetpass()
    {
        if (!CoreSession::connected()) {
            $ok = false;
            $mail = "";
            $login = CoreRequest::getString("login",
                                            "",
                                            CoreRequestType::POST);

            if (!empty($login)) {
                if (CoreSession::validLogin($login)) {
                    CoreSql::getInstance()->select(
                            CoreTable::USERS,
                            array(
                        "name, mail"),
                            array(
                        "name = '" . $login . "'")
                    );

                    if (CoreSql::getInstance()->affectedRows() == 1) {
                        list($name, $mail) = CoreSql::getInstance()->fetchArray();
                        if ($name == $login) {
                            // TODO Ajouter un générateur d'id
                            $ok = ExecMailer::sendMail(); // TODO envoyer un mail
                        }
                    }
                    if (!$ok) {
                        CoreLogger::addWarning(FORGET_PASSWORD_INVALID_LOGIN_ACCOUNT);
                    }
                } else {
                    $this->errorBox();
                }
            }

            if ($ok) {
                CoreLogger::addInfo(FORGET_PASSWORD_IS_SUBMIT_TO . " " . $mail);
            } else {
                if (CoreMain::getInstance()->isDefaultLayout() || empty($login)) {
                    $form = new LibForm("login-forgetpass");
                    $form->setTitle(FORGET_PASSWORD_TITLE);
                    $form->setDescription(FORGET_PASSWORD_DESCRIPTION);
                    $form->addInputText("login",
                                        LOGIN);
                    $form->addInputHidden("module",
                                          "connect");
                    $form->addInputHidden("view",
                                          "forgetpass");
                    $form->addInputHidden("layout",
                                          "module");
                    $form->addInputSubmit("submit",
                                          FORGET_PASSWORD_SUBMIT);

                    $moreLink = "<ul>";
                    if (CoreMain::getInstance()->getConfigs()->registrationAllowed()) {
                        $moreLink .= "<li><span class=\"text_bold\">" . CoreHtml::getLink("module=connect&view=registration",
                                                                                          LINK_TO_NEW_ACCOUNT) . "</span></li>";
                    }
                    $moreLink .= "<li>" . CoreHtml::getLink("module=connect&view=logon",
                                                            LINK_TO_LOGON) . "</li>"
                            . "<li>" . CoreHtml::getLink("module=connect&view=forgetlogin",
                                                         LINK_TO_FORGET_LOGIN) . "</li></ul>";

                    $form->addHtmlInFieldset($moreLink);

                    echo $form->render();
                    CoreHtml::getInstance()->addJavascript("validForgetPass('#form-login-forgetpass', '#form-login-forgetpass-login-input');");
                }
            }
        } else {
            $this->display();
        }
    }

    public function registration()
    {

    }
}