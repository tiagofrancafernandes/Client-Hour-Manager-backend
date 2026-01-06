<?php
$uri ??= request_uri();
?>

<ul data-uri="<?php echo $uri ?>" class="menu-container flex">
    <li><a data-is-active="<?= in_array($uri, ['/home']) ?>" href="/">Home</a></li>
    <li><a data-is-active="<?= in_array($uri, ['/contact']) ?>" href="/contact">Contact</a></li>
    <li><a data-is-active="<?= in_array($uri, ['/git']) ?>" href="/git">Git</a></li>
    <li><a data-is-active="<?= in_array($uri, ['/about']) ?>" href="/about">About</a></li>
    <li><a data-is-active="<?= in_array($uri, ['/libs']) ?>" href="/libs">Libs</a></li>
    <li><a data-is-active="<?= in_array($uri, ['/db']) ?>" href="/db">db</a></li>
    <li><a data-is-active="<?= in_array($uri, ['/page']) ?>" href="/page?page=about">Page ?</a></li>
</ul>
