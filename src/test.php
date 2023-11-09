<?php

use Heifetz\ProgressBar;

require(__DIR__ . '/../vendor/autoload.php');

$count = 12000000;
$pb = new ProgressBar($count);
$pb->setRenderDelay(100);

for ($i = 0; $i < $count; $i++) {
    $pb->advance();
    usleep(1000);
}

$pb->finish();
