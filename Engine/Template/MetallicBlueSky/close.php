<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo PassionEngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php PassionEngine\Engine\Core\CoreHtml::getInstance()->addCssTemplateFile('main_animation.css'); ?>
        <?php PassionEngine\Engine\Core\CoreHtml::getInstance()->addCssTemplateFile('close.css'); ?>
        <?php echo PassionEngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
    </head>
    <body>
        <div id="metallic_blue_sky">
            <div id="metallic_blue_sky_background"></div>
            <div style="display: none;">
                <?php echo PassionEngine\Engine\Core\CoreMain::getInstance()->getConfigs()->getDefaultSiteName(); ?>
                <?php echo PassionEngine\Engine\Core\CoreMain::getInstance()->getConfigs()->getDefaultSiteSlogan(); ?>
            </div>
            <div id="close_block">
                <div id="close_image"><a href="https://<?php echo PASSION_ENGINE_URL; ?>"></a></div>
                <div id="close_text">
                    <br /><?php echo $closeText; ?>
                    <br /><br /><?php echo $closeReason; ?>
                    <br />
                    <?php echo PassionEngine\Engine\Core\CoreMain::getInstance()->getRoute()->getRequestedBlockDataByType()->getFinalOutput(); ?>
                </div>
            </div>
        </div>
        <?php echo PassionEngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>
