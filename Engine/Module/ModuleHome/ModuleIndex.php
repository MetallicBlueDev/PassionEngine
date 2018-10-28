<?php

namespace TREngine\Engine\Module\ModuleHome;

use TREngine\Engine\Module\ModuleModel;

class ModuleIndex extends ModuleModel
{

    public function display(string $view): void
    {
        ?>
        <div class="title">
            <span>Bonjour, bienvenue sur <a href="index.php">le moteur PassionEngine</a>.</span>
        </div>
        <br /><br />
        <div class="description" style="width: 70%;">
            <span>Ceci est un exemple d'affichage.</span>
            <br /><br />
        </div>

        <?php
    }

    public function getViewList(): array
    {
        return array();
    }
}