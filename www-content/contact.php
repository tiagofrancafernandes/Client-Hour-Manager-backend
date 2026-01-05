<?php

$fileName = str_replace(
    ['/var/task/user/', '/index.php/', '/index.php'],
    '',
    $_SERVER['PHP_SELF'] ?? '',
);
$date = date('c');

function renderView(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);

    ob_start();
    include $template;

    return ob_get_clean();
}

$content = renderView(__DIR__.'/_partials/menu.view.php', ['date' => $date]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File: <?= $fileName ?></title>
</head>
<body>
    <?= $content . ' content' ?>
    <h3>
        This is the "<?= $fileName ?>" file/path. Go to <a href="/">home</a>
    </h3>
</body>
</html>
