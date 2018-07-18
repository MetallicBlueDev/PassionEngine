<div class="title"><span><?php
echo $title;
?></span></div>
<div class="description" style="width: 90%;"><span><?php
echo $description;
?></span></div>

<?php
$progress = "";
foreach ($projects as $key => $projectItem) {
    if ($projectItem['progress'] < 25) {
        $progress = "_25";
    } else if ($projectItem['progress'] < 50) {
        $progress = "_50";
    } else if ($projectItem['progress'] < 75) {
        $progress = "_75";
    } else {
        $progress = "";
    }
    ?>

    <?php
    echo TREngine\Engine\Core\CoreHtml::getLink('?module=project&" . CoreLayout::REQUEST_VIEW . "=displayProject&projectId=' . $projectItem['projectid'], '
        <div class="project_body' . $progress . '">
            <div>
                <div class="project_img"><img alt="" src="' . TREngine\Engine\Lib\LibMakeStyle::getTemplateDirectory() . '/project/' . strtolower($projectItem['language']) . '.png" /></div>
                <div class="project_text">
                    <span class=\"text_bold\">' . $projectItem['name'] . '</span> (' . $projectItem['language'] . ')
                    <br /><span class=\"text_underline\">' . PERCENT_COMPLETE . ': " ' . $projectItem['progress'] . '%</span>
                </div>
            </div>
        </div>');
    ?>

    <?php
}
?>

<div style="text-align: center;"><?php
echo $nbProjects;
?></div>