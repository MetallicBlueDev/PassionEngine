<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo PassionEngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php echo PassionEngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
    </head>
    <body>
        <div class="debugerror">
            <span class="text_bold"><?php echo $errorMessageTitle; ?></span>
            <div>
                <br /><br />
                Error details:<br />
                <ul>
                    <?php foreach ($debugMessages as $message) { ?>
                        <?php if (empty($message)) { ?>
                            <li><hr/></li>
                        <?php } else { ?>
                            <li><?php echo $message; ?></li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php echo PassionEngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>