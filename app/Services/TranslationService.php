<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Symfony\Component\Finder\Finder;

class TranslationService
{
    public static function findTranslations($command = null, $path = null): int
    {
        $path = $path ? base_path($path) : base_path();
        $keys = array();
        $functions = array(
            'trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
            'transEditable',
            '__'
        );
        $pattern =                              // See http://regexr.com/392hu
            "[^\w]" .                          // Must not have an alphanum or _ or > before real method
            "(" . implode('|', $functions) . ")" .  // Must start with one of the functions
            "\(" .                               // Match opening parenthese
            "[\'\"]" .                           // Match " or '
            "(" .                                // Start a new group to match:
            ".+" .               // Must start with group
            //            "([^\1)]+)+" .                // Be followed by one or more items/keys
            ")" .                                // Close group
            "[\'\"]" .                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter

        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();

        $finder->in($path)->exclude('storage')
            ->exclude('node_modules')
            ->exclude('public')
            ->exclude('test')
            ->exclude('vendor')
            ->name('*.php')->files();

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            $now = now();

            // Search the current file for the pattern
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    if (!$key) continue;
                    $keys[] = ['key' => $key];
                }
            }
            $command->line("{$file->getFilename()}--------------------------- {$now->diffInMilliseconds()}ms");
        }

        $keys = collect($keys)->unique("key");

        $translations = [];

        foreach ($keys as $key) {
            $translations[$key['key']] = $key['key'];
        }

        $enJsonPath = base_path('lang/en.json');


        if (! file_exists($enJsonPath)){
            if (! file_exists(base_path('lang'))){
                mkdir(base_path('lang'));
            }
            File::put($enJsonPath,"");
        }

        $oldTranslations = json_decode(file_get_contents($enJsonPath),true);

        $foundedTranslations = collect($translations)->unique();

        $count = 0;
        $newTranslations = [];
        foreach ($foundedTranslations as $key => $translation){

            if (!(@$oldTranslations[$key])){
                $newTranslations[$key] = $translation;
                $count++;
            }
        }
        file_put_contents($enJsonPath,collect(array_merge($newTranslations,($oldTranslations ??[])))->unique()->toJson(JSON_PRETTY_PRINT));
        $command->info($count . " new key\s found");
        return CommandAlias::SUCCESS;
    }

    public static function bulkTranslation(Command $command,string $locale = 'ar'): int
    {
        if (!file_exists(base_path('lang/en.json'))) {

            throw new \Exception('lang/en.json file doest exists');
        }

        $enTranslations = json_decode(file_get_contents(resource_path('lang/en.json')), true);

        $self = new self();

        $translations = [];

        $count = count($enTranslations);

        $down = $count;
        foreach ($enTranslations as $key => $text) {
            $command->line("$key -----------------------" . $down-- . ' of ' . $count);
            $translations[$key] = $self->apiTranslate($text, $locale);
        }

        $file = fopen(resource_path("lang/$locale.json"), "wb");

        $status = fwrite($file, json_encode($translations,JSON_PRETTY_PRINT));

        fclose($file);

        $command->info('Success translations');
        return CommandAlias::SUCCESS;
    }

    public function apiTranslate(string $text, string $iso_code = 'ar'): string
    {
        try {
            $client = new Client();

            $response = $client->get("https://api.mymemory.translated.net/get?q={$text}&langpair=en|$iso_code", [
                'headers' => [
                    'Content-type' => 'application/json'
                ]
            ]);

            $data = json_decode($response->getBody());

            if (isset($data->responseStatus) and $data->responseStatus == 200) {

                return $this->fixTranslationParams($text, $data->responseData->translatedText);
            }
            return $text;
        } catch (\Throwable $th) {
            Log::error($th);
            return $text;
        }
    }

    private function matchWordsStartWith(string $text, ?string $pattern = ":"): array|false|null
    {
        preg_match("/\b{$pattern}\w*\b/i", $text, $matches);

        preg_match_all("/$pattern\w*/i", $text, $output_array);

        preg_match_all(
            "/$pattern\p{L}*/u",
            $text,
            $output_array2
        );
        return $output_array2;
    }

    private function fixTranslationParams(string $rawText, string $translatedText): ?string
    {
        $matches = collect($this->matchWordsStartWith($rawText))->flatten()->filter(fn($i) => $i != ":" and $i != null)->toArray();
        $matches2 = collect($this->matchWordsStartWith($translatedText))->flatten()->filter(fn($i) => $i != ":" and $i != null)->toArray();

        if (count($matches) > 0 and count($matches2) > 0) {
            $replacements = array_combine($matches, $matches2);

            foreach ($replacements as $key => $replacement) {
                $translatedText = str_replace($replacement, $key, $translatedText);
            }
        }

        return $translatedText;
    }
}
