<?php

namespace TREngine\Engine\Module\ModuleReceiptbox;

use TREngine\Engine\Module\ModuleModel;

class ModuleIndex extends ModuleModel
{

    public function display()
    {
        echo "Bienvenue sur la messagerie !";
    }
}