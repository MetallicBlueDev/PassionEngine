<?php

use PassionEngine\Engine\Core\CoreUrlRewriting;

if (PassionEngine\Engine\Core\CoreMain::getInstance()->getRoute()->isDefaultLayout()) {
    ?>
    <div id="management_bar">
        <div>
            <?php
            echo PassionEngine\Engine\Core\CoreHtml::getLink('?module=management',
                                                             '<img alt="" src="' . PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . '/management/bar_home.png" />');
            ?>
        </div>
        <div>
            <?php
            echo PassionEngine\Engine\Core\CoreHtml::getLink('?module=management&manage=update',
                                                             '<img alt=""  src="' . PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . '/management/bar_update.png" />');
            ?>
        </div>
        <div id="management_bar_setting_page">
            <select onchange="document.location = '<?php
            echo CoreUrlRewriting::getLink('?module=management&manage=');
            ?>' + this.options[this.selectedIndex].value;">
                <option value=""></option>
                <?php
                foreach ($pageList as $page) {
                    $selected = ($page['value'] == $pageSelected) ? 'selected="selected"' : '';
                    ?>
                    <option value="<?php
                    echo $page['value'];
                    ?>" <?php
                            echo $selected;
                            ?>><?php
                            echo $page['name'];
                            ?></option>
                                <?php
                            }
                            ?>
            </select>
        </div>

        <div id="management_bar_module_page">
            <select onchange="document.location = '<?php
                echo PassionEngine\Engine\Core\CoreUrlRewriting::getLink('?module=management&manage=');
                ?>' + this.options[this.selectedIndex].value;">
                <option value=""></option>
            <?php
            foreach ($moduleList as $module) {
                $selected = ($module['value'] == $pageSelected) ? 'selected="selected"' : '';
                ?>
                    <option value="<?php
                    echo $module['value'];
                    ?>" <?php
                    echo $selected;
                    ?>><?php
                    echo $module['name'];
                    ?></option>
                            <?php
                        }
                        ?>
            </select>
        </div>
    </div>
                    <?php
                include PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . DIRECTORY_SEPARATOR . 'module_management_title.php';
            }
            ?>