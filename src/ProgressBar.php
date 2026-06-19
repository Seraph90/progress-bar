<?php

declare(strict_types=1);

namespace Heifetz;

class ProgressBar
{

    private float $startTime;

    private float $lastRenderTime;
    private int $renderDelayMilliseconds = 250;

    private int $counter = 0;
    private int $errorCounter = 0;
    private int $total;
    private int $lastRenderedTotalCounter = -1;

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
    private bool $isCursorHidden = false;
    private bool $isFinished = false;
    private bool $separateErrors = true;

    private function getTotalCounter(): float
    {
        return $this->counter + $this->errorCounter;
    }

    private function getPercent(): float
    {
        if (empty($this->total)) {
            return 0;
        }

        return min(100, $this->getTotalCounter() / $this->total * 100);
    }

    private function getCounterSize(): int
    {
        if (!$this->separateErrors) {
            return ($this->totalSize + 1) * 2;
        }

        return ($this->totalSize * 3) + 3;
    }

    private function getCounterText(): string
    {
        if (!$this->separateErrors) {
            return str_pad((string) $this->getTotalCounter(), $this->totalSize, ' ', STR_PAD_LEFT) . '/' . $this->total . ' ';
        }

        return self::FONT_GREEN
            . str_pad((string) $this->counter, $this->totalSize, ' ', STR_PAD_LEFT)
            . self::FONT_NORMAL
            . '/'
            . self::FONT_RED
            . str_pad((string) $this->errorCounter, $this->totalSize, ' ', STR_PAD_LEFT)
            . self::FONT_NORMAL
            . '/'
            . $this->total
            . ' ';
    }

    private function getEta(): string
    {
        if ($this->getTotalCounter() === 0.0) {
            return 'ETA: 00:00:00';
        }

        $currentTime = microtime(true) - $this->startTime;

        $seconds = (int) max(0, round($currentTime / $this->getTotalCounter() * $this->total - $currentTime));

        return 'ETA: ' . sprintf(
                '%02d:%02d:%02d',
                intdiv($seconds, 3600),
                intdiv($seconds % 3600, 60),
                $seconds % 60
            );
    }

    private function calcScreenSizes(): void
    {
        $this->screenLength = 80;

        if (function_exists('getenv')) {
            $columns = (int) getenv('COLUMNS');
            if ($columns > 0) {
                $this->screenLength = $columns;
            }
        }

        if (!function_exists('shell_exec')) {
            return;
        }

        $output = shell_exec('stty size 2>/dev/null');
        if (!is_string($output)) {
            return;
        }

        $screenSizes = preg_split('/\s+/', trim($output));
        $screenLength = (int) ($screenSizes[1] ?? 0);
        if ($screenLength > 0) {
            $this->screenLength = $screenLength;
        }
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

        if (($this->subSize + $this->getCounterSize()) < $this->screenLength - 2) {
            $this->showCounter = true;
            $this->subSize += $this->getCounterSize();
        }

        if ($this->subSize + 1 + self::ETA_SIZE < $this->screenLength - 2) {
            $this->showEta = true;
            $this->subSize += 1 + self::ETA_SIZE;
        }
    }

    private function clearTerminal(): void
    {
        echo "\033[2J"; // Clear all screen
        echo "\033[0;0H"; // Set cursor top left corner
    }

    private function isFinished(): bool
    {
        return $this->getTotalCounter() >= $this->total;
    }

    private function shouldRender(): bool
    {
        $isRenderTime = microtime(true) - $this->lastRenderTime >= $this->renderDelayMilliseconds / 1000;

        return $isRenderTime || $this->isFinished();
    }

    private function getShortLoaderIndex(): int
    {
        $period = floor((microtime(true) - $this->startTime) * 10);

        return $period % count(self::SHORT_LOADER);
    }

    private function drawProgressBarLine(): void
    {
        $this->lastRenderTime = microtime(true);
        $this->lastRenderedTotalCounter = (int) $this->getTotalCounter();

        $percent = $this->getPercent();

        $progressBar = "\r" . self::FONT_NORMAL;
        if ($this->showCounter) {
            $progressBar .= $this->getCounterText();
        }

        $progressBar .= self::FONT_GREEN;

        if ($this->useLoader) {
            $progressBar .= $this->isFinished() ? self::SHORT_LOADER_FINISH : self::SHORT_LOADER[$this->getShortLoaderIndex()];
        } else {
            $fullBar = $this->screenLength - $this->subSize;
            $completedBars = (int) floor($fullBar * $this->getTotalCounter() / $this->total);
            $successCounter = $this->separateErrors ? $this->counter : $this->getTotalCounter();
            $percentsBars = (int) floor($fullBar * $successCounter / $this->total);
            $errorPercentsBars = $this->separateErrors ? $completedBars - $percentsBars : 0;
            $emptyBars = $fullBar - $completedBars;
            $emptyBars = max(0, $emptyBars);

            $progressBar .= str_repeat('█', $percentsBars);
            if (!empty($errorPercentsBars)) {
                $progressBar .= self::FONT_RED;
                $progressBar .= str_repeat('█', $errorPercentsBars);
                $progressBar .= self::FONT_GREEN;
            }
            $progressBar .= str_repeat('▒', $emptyBars);
            if ($this->showEta) {
                $progressBar .= ' ' . $this->getEta();
            }
        }

        if ($this->showPercent) {
            $progressBar .= ' ' . self::FONT_NORMAL . self::FONT_BOLD . str_pad(number_format($percent, 2), 6, ' ', STR_PAD_LEFT) . '%';
        }

        echo $progressBar;
    }

    public function __construct(int $total)
    {
        if ($total <= 0) {
            throw new \InvalidArgumentException('Total count must be greater than zero.');
        }

        $this->total = $total;
        $this->startTime = microtime(true);
        $this->lastRenderTime = microtime(true);

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

        $this->hideCursor();
    }

    public function setRenderDelay(int $milliseconds): self
    {
        $this->renderDelayMilliseconds = max(0, $milliseconds);

        return $this;
    }

    public function setSeparateErrors(bool $enabled): self
    {
        $this->separateErrors = $enabled;
        $this->calcSubSize();

        return $this;
    }

    private function step(bool $isError = false): void
    {
        if ($this->isFinished()) {
            return;
        }

        pcntl_signal_dispatch();

        if ($isError && $this->separateErrors) {
            $this->errorCounter++;
        } else {
            $this->counter++;
        }

        if ($this->shouldRender()) {
            $this->drawProgressBarLine();
        }
    }

    public function advance(): void
    {
        $this->step();
    }

    public function advanceError(): void
    {
        $this->step(true);
    }

    public function finish(): void
    {
        if ($this->isFinished) {
            return;
        }

        if ($this->lastRenderedTotalCounter !== (int) $this->getTotalCounter()) {
            $this->drawProgressBarLine();
        }

        $this->showCursor();
        echo PHP_EOL;

        $this->isFinished = true;
    }

    public function __destruct()
    {
        $this->showCursor();
    }

    private function hideCursor(): void
    {
        if ($this->isCursorHidden) {
            return;
        }

        echo self::HIDE_CARET;
        $this->isCursorHidden = true;
    }

    private function showCursor(): void
    {
        if (!$this->isCursorHidden) {
            return;
        }

        echo self::SHOW_CARET;
        $this->isCursorHidden = false;
    }

}
