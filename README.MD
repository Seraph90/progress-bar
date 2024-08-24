# ProgressBar – Class for displaying a progressbar in your console applications

![GitHub Release](https://img.shields.io/github/v/release/alex-heifetz/progress-bar)
![GitHub Downloads (all assets, all releases)](https://img.shields.io/packagist/dt/alex-heifetz/progress-bar)
![GitHub License](https://img.shields.io/github/license/alex-heifetz/progress-bar)
![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/alex-heifetz/progress-bar/php)

![Gif](https://github.com/Seraph90/my-gifs/blob/main/gifs/error-wait.gif?raw=true)

## Features
- Compatible with PHP 7.4 and later
- Displays estimated completion time
- It is possible to divide into 2 parts, for example with successful iterations and with erroneous ones
- Flexibly responds to changes in window width
- It is possible to configure the data update time

## Why you might need this
Sometimes when running commands and long processes or testing commands, you want to see that the process is moving and not hanging in the middle.
I would also like to understand the approximate completion time of the process.
This library was created to solve these problems.

## Installation & loading
Just add this line to your `composer.json` file:

```json
"alex-heifetz/progress-bar": "^1.2"
```

or run

```sh
composer require seraph90/progress-bar
```

## A Simple Example

```php
<?php

// Import ProgressBar class into the global namespace
use Heifetz\ProgressBar;

// Load Composer's autoloader
require 'vendor/autoload.php';

$count = 12000000;

// Create an instance;
// Passing total count of iterations 
$pb = new ProgressBar($count);

// Set the delay time between redrawings
$pb->setRenderDelay(100);

for ($i = 0; $i < $count; $i++) {
    if (mt_rand(0, 5)) {
        // Advance the process by one iteration
        $pb->advance();
    } else {
        // Advance the process by one iteration with error
        $pb->advanceError();
    }
}

// We indicate the completion of the progress bar to render the final state
$pb->finish();
```

![ProgresBar](https://github.com/Seraph90/my-gifs/blob/main/gifs/ProgressBarWithErrors.gif?raw=true)
