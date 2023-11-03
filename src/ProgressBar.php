<?php

declare(strict_types=1);

namespace Heifetz;

class ProgressBar
{

    private int $counter = 0;
    private int $total;
    private int $totalSize;
    private int $screenLength;
    private int $subSize;

    private float $startTime;

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

    public function __construct(int $total)
    {
        $this->total = $total;
        $this->startTime = microtime(true);

        $this->totalSize = strlen((string) $total) + 1;

        $sizes = explode(' ', trim(shell_exec('stty size') ?? '80'));
        $this->screenLength = (int) $sizes[1];
        $this->subSize = $this->totalSize * 2 + 25;
    }

    public function advance(): void
    {
        $this->counter++;
        $percent = $this->getPercent();

        echo "\r" . str_repeat(' ', $this->screenLength);
        $progressBar = "\r \033[0m";
        $progressBar .= str_pad((string) $this->counter, $this->totalSize, ' ', STR_PAD_LEFT) . '/' . $this->total . ' ';
        $progressBar .= "\033[32m\033[1m";
        $percents = (int) round(($this->screenLength - $this->subSize) / 100 * $percent);
        $progressBar .= str_repeat('█', $percents + 1);
        $progressBar .= str_repeat('▒', intval(max($this->screenLength - $this->subSize - $percents, 0)));
        $progressBar .= ' ' . $this->getEta();
        echo $progressBar . ' ' . $percent . '%' . "\033[0m";
    }

}
