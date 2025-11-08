<?php

namespace App\Console\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;

class FindTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:find';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'search about translations keys in files ';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        return TranslationService::findTranslations($this);
    }
}
