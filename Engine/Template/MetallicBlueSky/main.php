<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php TREngine\Engine\Core\CoreHtml::getInstance()->addCssTemplateFile("engine_animation.css"); ?>
        <?php TREngine\Engine\Core\CoreHtml::getInstance()->addCssTemplateFile("main_animation.css"); ?>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
    </head>
    <body>
        <div id="metallic_blue_sky">
            <div id="metallic_blue_sky_background"></div>
            <div id="metallic_blue_sky_screen">
                <header id="header">
                    <div style="display: none;"><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getConfigs()->getDefaultSiteName(); ?></div>
                    <div id="header_left">
                        <div class="header_wire_harness wire_harness_left">
                            <div class="wire_harness_object wire_harness_anim1"></div>
                            <div class="wire_harness_object wire_harness_anim2"></div>
                            <div class="wire_harness_object wire_harness_anim1"></div>
                        </div>
                        <a href="index.php" title="<?php echo TREngine\Engine\Core\CoreMain::getInstance()->getConfigs()->getDefaultSiteName(); ?>"></a>
                        <h3><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getConfigs()->getDefaultSiteSlogan(); ?></h3>
                    </div>
                    <div id="header_middle"></div>
                    <div id="header_right">
                        <div class="header_wire_harness wire_harness_right">
                            <div class="wire_harness_object wire_harness_anim1"></div>
                            <div class="wire_harness_object wire_harness_anim2"></div>
                            <div class="wire_harness_object wire_harness_anim1"></div>
                        </div>
                    </div>
                </header>
                <div id="wrapper">
                    <div id="wrapper_leftfix">
                        <div id="wrapper_rightfix">
                            <div id="wrapper_left">
                                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_LEFT); ?>
                            </div>
                            <div id="wrapper_middlefix">
                                <div id="wrapper_middle">
                                    <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getLoader(); ?>
                                    <?php echo TREngine\Engine\Lib\LibBreadcrumb::getInstance()->getBreadcrumbTrail(); ?>
                                    <div class="spacer"></div>
                                    <?php TREngine\Engine\Core\CoreLogger::displayMessages(); ?>
                                    <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_TOP); ?>
                                    <div>
                                        <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_MODULE_LEFT); ?>
                                    </div>
                                    <div>
                                        <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_MODULE_TOP); ?>
                                    </div>
                                    <div id="module_top">
                                        <div id="module_top_left"></div>
                                        <div id="module_top_middle"></div>
                                        <div id="module_top_right"></div>
                                    </div>
                                    <div id="module_content_leftfix">
                                        <div id="module_content_rightfix">
                                            <div id="module_content_middle">
                                                <?php echo TREngine\Engine\Core\CoreMain::getInstance()->getRoute()->getRequestedModuleData()->getFinalOutput(); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="module_bottom">
                                        <div id="module_bottom_left"></div>
                                        <div id="module_bottom_middle"></div>
                                        <div id="module_bottom_right"></div>
                                    </div>
                                    <div>
                                        <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_MODULE_BOTTOM); ?>
                                    </div>
                                    <div>
                                        <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_MODULE_RIGHT); ?>
                                    </div>
                                    <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_BOTTOM); ?>
                                </div>
                            </div>
                            <div id="wrapper_right_compatibility">
                                <div id="wrapper_quick_lighting_container1" class="wrapper_quick_lighting_container"></div>
                                <div id="wrapper_quick_lighting_container2" class="wrapper_quick_lighting_container"></div>
                                <div id="wrapper_quick_lighting_container3" class="wrapper_quick_lighting_container"></div>
                                <div id="wrapper_quick_lighting_container4" class="wrapper_quick_lighting_container"></div>
                                <div id="wrapper_right">
                                    <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBuildedBySide(TREngine\Engine\Core\CoreLayout::BLOCK_SIDE_RIGHT); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer id="footer">
                    <div id="footer_left"></div>
                    <div id="footer_middle">
                        <div style="padding: 50px;">
                            Page g&eacute;n&eacute;r&eacute;e en <?php echo TREngine\Engine\Exec\ExecTimeMarker::getMeasurement("main"); ?> ms.
                        </div>
                    </div>
                    <div id="footer_right"></div>
                </footer>
            </div>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>