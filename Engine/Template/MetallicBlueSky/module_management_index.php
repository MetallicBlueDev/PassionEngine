<?php include PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . DIRECTORY_SEPARATOR . 'module_management_bar.php'; ?>

<div id="management_index_page">
    <?php
    foreach ($pageList as $page) {
        $pictureName = is_file(PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . DIRECTORY_SEPARATOR . 'management' . DIRECTORY_SEPARATOR . 'icon_' . $page['value'] . '.png') ? $page['value'] : 'no_picture';
        ?>
        <div class="management_index_block">
            <?php echo PassionEngine\Engine\Core\CoreHtml::getLink('?module=management&manage=' . $page['value'],
                                                                                '<img alt="" src="' . PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . '/management/icon_' . $pictureName . '.png" style="border: 0;" /><div><span class="text_bold">' . $page['name'] . '</span></div>'); ?>
        </div>
        <?php
    }
    ?>
</div>
<div class="spacer"></div>