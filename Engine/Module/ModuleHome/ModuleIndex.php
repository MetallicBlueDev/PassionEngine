<?php

namespace TREngine\Engine\Module\ModuleHome;

use TREngine\Engine\Module\ModuleModel;

class ModuleIndex extends ModuleModel
{

    public function display()
    {
        ?>
        <div class="title">
            <span>Bonjour, bienvenue sur <a href="index.php">Trancer-Studio.net</a>.</span>
        </div>
        <br /><br />
        <div class="description" style="width: 70%;">
            <span>Le site est un peu vide, mais il va se remplir petit &#224; petit...</span>
            <br /><br />A venir : des textes, de la documentation, des exemples, des images relatif &#224; mes projets.
        </div>

        <?php
    }

    public function setting()
    {
        return "Pas de setting...";
    }
}