<?php

$fileName = str_replace(
    '/var/task/user/',
    '',
    $_SERVER['PHP_SELF'] ?? '',
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File: <?= $fileName ?></title>
</head>
<body>
    <?=  file_get_contents(__DIR__.'/_partials/menu.view.php') ?>
    <h3>
        This is the "<?= $fileName ?>" file. Go to <a href="/">home</a>
    </h3>
</body>
</html>
