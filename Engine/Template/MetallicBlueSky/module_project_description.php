<div class="project_description_header">
    <div>
        <div class="project_description_img">
            <?php
            if (!empty($projectInfo['img'])) {
                ?>
                <a href="<?php echo $projectInfo['img']; ?>">
                    <?php echo PassionEngine\Engine\Exec\ExecImage::getTag($projectInfo['img'], 128, 128); ?>
                </a>
                <?php
            } else {
                ?>
                <img src="<?php echo PassionEngine\Engine\Lib\LibMakeStyle::getTemplateDirectory(); ?>/project/<?php echo $projectInfo['language']; ?>.png" alt="" />
                <?php
            }
            ?>
        </div>
        <div class="project_text, description">
            <span><?php echo "<span class=\"text_bold\">" . LANGUAGE_TYPE . "</span>: " . $projectInfo['language']; ?></span>
            <br />
            <br /><?php echo "<span class=\"text_bold\">" . RECORDED_DATE . "</span>: " . $projectInfo['date']; ?>
            <br /><?php echo "<span class=\"text_bold\">" . PERCENT_COMPLETE . "</span>: " . $projectInfo['progress']; ?>%
            <br /><?php echo "<span class=\"text_bold\">" . OFFICIAL_WEBSITE . "</span>: " . (!empty($projectInfo['website']) ? $projectInfo['website'] : PASSION_ENGINE_URL); ?>
        </div>
    </div>
</div>
