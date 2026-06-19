# ProgressBar - PHP CLI progress bar

![GitHub Release](https://img.shields.io/github/v/release/alex-heifetz/progress-bar)
![GitHub Downloads (all assets, all releases)](https://img.shields.io/packagist/dt/alex-heifetz/progress-bar)
![GitHub License](https://img.shields.io/github/license/alex-heifetz/progress-bar)
![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/alex-heifetz/progress-bar/php)


![Gif](https://github.com/Seraph90/my-gifs/blob/main/gifs/error-wait.gif?raw=true)

## Features
- Compatible with PHP 7.4 and later
- Displays estimated completion time
- Separates successful and failed iterations
- Responds to terminal width changes
- Lets you configure the redraw interval

## Why you might need this
When running long CLI commands, background jobs, or test scripts, it is useful to see that the process is still moving and estimate when it will finish.
This library provides a compact progress bar for those cases.

## Requirements
- PHP 7.4+
- `ext-pcntl`
- A terminal with ANSI escape sequence support

## Installation & loading
Install the package with Composer:

```sh
composer require alex-heifetz/progress-bar
```

Or add it to your `composer.json` manually:

```json
{
    "require": {
        "alex-heifetz/progress-bar": "^1.4"
    }
}
```

## A Simple Example

```php
<?php

// Import ProgressBar class into the global namespace
use Heifetz\ProgressBar;

// Load Composer's autoloader
require 'vendor/autoload.php';

$count = 12000000;

// Create an instance with the total number of iterations.
$pb = new ProgressBar($count);

// Set the redraw delay in milliseconds.
$pb->setRenderDelay(100);

// Optional: count errors separately.
// Disable this if failed iterations should be counted as completed iterations.
$pb->setSeparateErrors(true);

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

![ProgressBar](https://github.com/Seraph90/my-gifs/blob/main/gifs/ProgressBarWithErrors.gif?raw=true)
