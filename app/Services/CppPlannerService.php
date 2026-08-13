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

    // public function generate(array $input): ?string
    // {
    //     $jobId = Str::uuid()->toString();
    //     $inputPath = $this->storagePath."/input_{$jobId}.json";
    //     // 1- حفظ input.json
    //     file_put_contents(
    //         $inputPath,
    //         json_encode(
    //             $input,
    //             JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    //         )
    //     );
    //     $outputPath = $this->storagePath."/output_{$jobId}.json";
    //     if (file_exists($outputPath)) {
    //         unlink($outputPath);
    //     }

    //     // 2- تشغيل cpp
    //     $process = new Process([
    //         $this->plannerPath,
    //         basename($inputPath),
    //         basename($outputPath),
    //     ], $this->storagePath);
    //     $process->setTimeout(120);
    //     $process->run();
    //     if (!$process->isSuccessful()) {
    //         throw new \Exception(
    //             $process->getErrorOutput()
    //         );
    //     }

    //     // 3- قراءة output.json
    //     if (!file_exists($outputPath)) {
    //         return null;
    //     }
    //     $output = json_decode(
    //         file_get_contents($outputPath),
    //         true
    //     );

    //     // حذف الملفات المؤقتة
    //     if (file_exists($inputPath)) {
    //         unlink($inputPath);
    //     }

    //     // if (file_exists($outputPath)) {
    //     //     unlink($outputPath);
    //     // }

    //     return $outputPath;
    //     // return $output;
    // }

    public function generate(array $input): ?string
    {
        $jobId = Str::uuid()->toString();

        $inputPath = $this->storagePath . "/input_{$jobId}.json";
        $outputPath = $this->storagePath . "/output_{$jobId}.json";

        try {
            // 1. إنشاء input.json الخاص بهذا الطلب
            file_put_contents(
                $inputPath,
                json_encode(
                    $input,
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                )
            );

            // إذا كان في output قديم بنفس الـ UUID (نظرياً مستحيل)
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }

            // 2. تشغيل C++
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

            // إذا الـ C++ فشل فعلياً
            if (!$process->isSuccessful()) {
                throw new \Exception(
                    $process->getErrorOutput()
                );
            }

            // 3. إذا ما طلع output فهذا يعني ما في خطة
            if (!file_exists($outputPath)) {
                return null;
            }

            // 4. رجع مسار output للـ PlanGenerationService
            return $outputPath;

        } finally {

            // 5. حذف input دائماً
            if (file_exists($inputPath)) {
                unlink($inputPath);
            }

            // مهم:
            // لا نحذف output هون لأن PlanResponseService
            // لسا بحاجة يقرأه.
        }
    }
}