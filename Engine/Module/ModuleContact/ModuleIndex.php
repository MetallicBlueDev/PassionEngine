<?php

namespace PassionEngine\Engine\Module\ModuleContact;

use PassionEngine\Engine\Module\ModuleModel;

class ModuleIndex extends ModuleModel
{

    public function display()
    {
        echo "Si vous souhaitez me contacter, le plus simple est de m'envoyer un e-mail (<a href=\"mailto:villemain.s_AT_gmail.com\">gmail.com</a>)"
        . "<br />Retrouvez mes coordonn&#233;es compl&#232;te sur <a href=\"?" . CoreLayout::REQUEST_MODULE . "=profilcv\">mon profil</a>.";
    }

    public function setting()
    {
        return "Pas de setting...";
    }
}