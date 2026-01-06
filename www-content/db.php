<?php

$fileName = str_replace(
    ['/var/task/user/', '/index.php/', '/index.php'],
    '',
    $_SERVER['PHP_SELF'] ?? '',
);

$pdoSqlite = new \PDO('sqlite::memory:');

$pgConfig = parse_url(getenv('DATABASE_URL') ?: '');
$pgHost = $pgConfig['host'] ?? 'localhost';
$pgPort = $pgConfig['port'] ?? 5432;
$pgPath = $pgConfig['path'] ?? '/mydb';
$pgUser = $pgConfig['user'] ?? 'postgres';
$pgPass = $pgConfig['pass'] ?? 'postgresPass';

$pdoPg = new \PDO("pgsql:host={$pgHost};port={$pgPort};dbname=" . ltrim($pgPath, '/'), $pgUser, $pgPass);
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

    // -c "SELECT 1;"
    dump([
        'test_conn_sqlite' => $pdoSqlite->query('SELECT 1')->fetchAll(),
        'test_conn_pg' => $pdoPg->query('SELECT 1')->fetchAll(),
    ]);
?>
</body>
</html>
