<?php

$fileName = str_replace(
    '/var/task/user/',
    '',
    $_SERVER['PHP_SELF'] ?? '',
);

var_export([
    $fileName,
    $_SERVER,
]);

die;
