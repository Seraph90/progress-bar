<?php

use Heifetz\ProgressBar;

require(__DIR__ . '/../vendor/autoload.php');

$count = 12000000;
$pb = new ProgressBar($count);
$pb->setRenderDelay(100);

for ($i = 0; $i < $count; $i++) {
    if (mt_rand(0, 5)) {
        $pb->advance();
    } else {
        $pb->advanceError();
    }
}

$pb->finish();
