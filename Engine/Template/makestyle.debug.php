<!DOCTYPE html>
<html lang="en">
    <div>
        <h1><?php echo $errorMessageTitle; ?></h1>
        <div>
            <br /><br />
            Error details:
            <br />
            <ul>
                <?php foreach ($debugMessages as $message) { ?>
                    <li><?php echo $message; ?></li>
                <?php } ?>
            </ul>
        </div>
    </div>
</html>