<?php

use Heifetz\ProgressBar;

require(__DIR__ . '/../vendor/autoload.php');

$count = 12000000;
$pb = new ProgressBar($count);
for ($i = 0; $i < $count; $i++) {
    $pb->advance();
    usleep(500);
}
$pb->finish();
