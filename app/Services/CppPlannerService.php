<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class CppPlannerService
{
    private string $plannerPath;
    private string $storagePath;

    public function __construct()
    {
        $this->plannerPath = base_path('cpp/planner.exe');
        $this->storagePath = base_path('cpp');
    }

    public function generate(array $input): array|null
    {
        // 1- حفظ input.json
        file_put_contents(
            $this->storagePath.'/input.json',
            json_encode(
                $input,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );
        $outputPath = $this->storagePath.'/output.json';
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }

        // 2- تشغيل cpp
        $process = new Process([$this->plannerPath], $this->storagePath);
        $process->setTimeout(120);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \Exception(
                $process->getErrorOutput()
            );
        }

        // 3- قراءة output.json
        if (!file_exists($outputPath)) {
            return null;
        }
        $output = file_get_contents($outputPath);

        return json_decode(
            $output,
            true
        );
    }
}