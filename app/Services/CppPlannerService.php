<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class CppPlannerService
{
    private string $plannerPath;
    private string $storagePath;

    public function __construct()
    {
        $this->plannerPath = base_path('cpp/planner.exe');
        $this->storagePath = base_path('cpp');
    }

    public function generate(array $input): ?string
    {
        $jobId = Str::uuid()->toString();
        $inputPath = $this->storagePath . "/input_{$jobId}.json";
        $outputPath = $this->storagePath . "/output_{$jobId}.json";

        try {
            file_put_contents(
                $inputPath,
                json_encode(
                    $input,
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                )
            );
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            $process = new Process(
                [
                    $this->plannerPath,
                    basename($inputPath),
                    basename($outputPath),
                ],
                $this->storagePath
            );
            $process->setTimeout(120);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(
                    $process->getErrorOutput()
                );
            }
            if (!file_exists($outputPath)) {
                return null;
            }
            return $outputPath;
        } finally {
            if (file_exists($inputPath)) {
                unlink($inputPath);
            }
        }
    }
}