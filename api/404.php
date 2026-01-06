<?php

declare(strict_types=1);
ob_start();

require_once __DIR__ . '/../core/bootstrap.php';

if (filter_var(request_any_get('tracy', false), FILTER_VALIDATE_BOOL)) {
    require_once __DIR__ . '/tracy.php';
}

$uri = request_uri();

if (!headers_sent()) {
    header("HTTP/1.1 404 Not found");

    http_response_code(404);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Found "<?= $uri ?>"</title>
    <?=  www_content_view('_partials/head_styles', []) ?>
</head>
<body>
    <?=  www_content_view('_partials/menu', []) ?>
    <h3>Not Found "<?= $uri ?>"</h3>

    <h5>Go to <a href="/">home</a></h5>
</body>
</html>
