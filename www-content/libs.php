<?php

$fileName = str_replace(
    ['/var/task/user/', '/index.php/', '/index.php'],
    '',
    $_SERVER['PHP_SELF'] ?? '',
);

$latestLog = git_latest_log();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File: <?= $fileName ?></title>
    <?=  www_content_view('_partials/head_styles', []) ?>
</head>
<body>
    <?=  www_content_view('_partials/menu', []) ?>
    <h3>
        This is the "<?= $fileName ?>" file/path. Go to <a href="/">home</a>
    </h3>

    <?php

    dump([
        'a' => 1,
        'b' => 2,
        'c' => 3,
        'file_line' => __FILE__ . ':' . __LINE__,
        'git_latest_log' => git_latest_log(),
        'Illuminate\Support\Carbon' => (new Illuminate\Support\Carbon())->toDateTimeString(),
        'now' => now(),
    ]);
?>
</body>
</html>
