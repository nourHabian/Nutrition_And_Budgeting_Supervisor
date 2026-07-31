<?php

namespace App\Console\Commands;

use App\Services\DatasetImporterService;
use Illuminate\Console\Command;

class ImportDatasets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datasets:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(DatasetImporterService $importer)
    {
        $importer->importAll();
        $this->info("Done.");
    }
}
