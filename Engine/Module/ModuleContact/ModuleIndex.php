<?php

namespace PassionEngine\Engine\Module\ModuleContact;

use PassionEngine\Engine\Module\ModuleModel;

class ModuleIndex extends ModuleModel
{

    public function display()
    {
        echo "Module contact.";
    }

    public function setting()
    {
        return "Pas de setting...";
    }
}