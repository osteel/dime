<?php

$tag = $argv[1];
$phar = sprintf('https://github.com/osteel/dime/releases/download/%s/dime', $tag);
$release = sprintf('https://github.com/osteel/dime/releases/tag/%s', $tag);

$readme = preg_replace(
    '#<!-- phar -->.*<!-- /phar -->#s',
    sprintf("<!-- phar -->\n[Download the PHAR archive](%s) from the [latest release](%s).\n<!-- /phar -->", $phar, $release),
    file_get_contents('README.md')
);

file_put_contents('README.md', $readme);
