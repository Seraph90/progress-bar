<?php

declare(strict_types=1);

namespace Heifetz;

class ProgressBar
{

    private float $startTime;

    private int $counter = 0;
    private int $total;

    private int $totalSize;
    private int $screenLength;
    private int $subSize;

    const SHORT_LOADER_FINISH = '⠿';
    const SHORT_LOADER = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];

    const HIDE_CARET = "\033[?25l";
    const SHOW_CARET = "\033[?25h";

    const FONT_BOLD = "\033[1m";

    const FONT_GREEN = "\033[32m";
    const FONT_RED = "\033[31m";
    const FONT_NORMAL = "\033[0m";

    const ETA_SIZE = 14;
    const PERCENT_SIZE = 8;

    private bool $showCounter = true;
    private bool $showEta = true;
    private bool $showPercent = true;
    private bool $useLoader = false;

    private function getPercent(): float
    {
        if (empty($this->total)) {
            return 0;
        }

        return round($this->counter / $this->total * 100, 2);
    }

    private function getEta(): string
    {
        $currentTime = microtime(true) - $this->startTime;

        $seconds = $currentTime / $this->counter * $this->total - $currentTime;

        return 'ETA: ' . sprintf('%02d:%02d:%02d', ($seconds / 3600), ($seconds / 60 % 60), $seconds % 60);
    }

    private function calcScreenSizes(): void
    {
        $screenSizes = explode(' ', trim(shell_exec('stty size')));
        // Get screen width
        $this->screenLength = (int) ($screenSizes[1] ?? 80);
    }

    /** Calculate occupied space in line */
    private function calcSubSize(): void
    {
        $this->subSize = 1;

        $this->showCounter = false;
        $this->showEta = false;
        $this->useLoader = true;

        if ($this->screenLength <= self::PERCENT_SIZE + 1) {
            $this->showPercent = false;
        } elseif ($this->screenLength <= self::PERCENT_SIZE + 3) {
            $this->showPercent = true;
        } else {
            $this->useLoader = false;
            $this->showPercent = true;
        }

        if ($this->showPercent) {
            $this->subSize += 1 + self::PERCENT_SIZE;
        }

        if (($this->subSize + ($this->totalSize + 1) * 2) < $this->screenLength - 2) {
            $this->showCounter = true;
            $this->subSize += ($this->totalSize + 1) * 2;
        }

        if ($this->subSize + 1 + self::ETA_SIZE < $this->screenLength - 2) {
            $this->showEta = true;
            $this->subSize += 1 + self::ETA_SIZE;
        }
    }

    private function clearTerminal()
    {
        echo "\033[2J"; // Clear all screen
        echo "\033[0;0H"; // Set cursor top left corner
    }

    public function __construct(int $total)
    {
        $this->total = $total;
        $this->startTime = microtime(true);

        $this->totalSize = strlen((string) $total);

        $this->calcScreenSizes();
        $this->calcSubSize();

        pcntl_signal(SIGWINCH, function ($signal) {
            if ($signal == SIGWINCH) {
                $this->clearTerminal();
                $this->calcScreenSizes();
                $this->calcSubSize();
            }
        });

        echo self::HIDE_CARET; // Скрыть курсор
    }

    private function clearLine(): void
    {
        echo "\r" . str_repeat(' ', $this->screenLength);
    }

    public function advance(): void
    {
        if ($this->counter >= $this->total) {
            return;
        }

        pcntl_signal_dispatch();

        $this->counter++;
        $percent = $this->getPercent();

        $progressBar = "\r" . self::FONT_NORMAL;
        if ($this->showCounter) {
            $progressBar .= str_pad((string) $this->counter, $this->totalSize, ' ', STR_PAD_LEFT) . '/' . $this->total . ' ';
        }

        $progressBar .= self::FONT_GREEN;;

        $percents = (int) round(($this->screenLength - $this->subSize) / 100 * $percent);
        if ($this->useLoader) {
            $period = floor((microtime(true) - $this->startTime) * 10);
            $progressBar .= self::SHORT_LOADER[$period % count(self::SHORT_LOADER)];
        } else {
            $progressBar .= str_repeat('█', $percents + 1);
            $progressBar .= str_repeat('▒', intval(max($this->screenLength - $this->subSize - $percents, 0)));
            if ($this->showEta) {
                $progressBar .= ' ' . $this->getEta();
            }
        }

        if ($this->showPercent) {
            $progressBar .= ' ' . self::FONT_NORMAL . self::FONT_BOLD . str_pad(number_format($percent, 2), 6, ' ', STR_PAD_LEFT) . '%';
        }

        echo $progressBar;
    }

    public function finish(): void
    {
        echo self::SHOW_CARET . PHP_EOL;
    }

}
