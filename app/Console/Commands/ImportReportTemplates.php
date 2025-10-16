<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportReportTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:report-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads the user-friendly templates file and converts it into the application config file.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting report template import...');

        // 1. Read the user-friendly text file
        $filePath = 'templates/vorlagen.txt';
        if (!Storage::exists($filePath)) {
            $this->error('Error: The source file storage/app/templates/vorlagen.txt was not found.');
            return 1;
        }
        $content = Storage::get($filePath);

        // 2. Parse the content
        $templatesArray = [];
        $processedHashes = [];
        // Split templates by the [NEUE VORLAGE] marker
        $templateBlocks = preg_split('/\[NEUE VORLAGE\]/', $content, -1, PREG_SPLIT_NO_EMPTY);


        foreach ($templateBlocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            // KORRIGIERT: Use a single, robust regular expression to capture all parts
            $pattern = '/^NAME:\s*(.*?)\s*TITEL:\s*(.*?)\s*---\s*EINSATZHERGANG\s*---\s*(.*?)\s*---\s*MASSNAHMEN\s*---\s*(.*)/s';
            
            if (preg_match($pattern, $block, $matches)) {
                // $matches[1] = Name, $matches[2] = Title, $matches[3] = Description, $matches[4] = Actions
                $name = trim($matches[1]);
                $title = trim($matches[2]);
                $incidentDescription = trim($matches[3]);
                $actionsTaken = trim($matches[4]);

                // Duplicate check
                $contentSignature = $name . $title . $incidentDescription . $actionsTaken;
                $contentHash = md5($contentSignature);

                if (in_array($contentHash, $processedHashes)) {
                    $this->warn("Skipping duplicate template content found with name '{$name}'.");
                    continue;
                }
                $processedHashes[] = $contentHash;

                // Build the array structure
                $templateKey = Str::slug(strtolower($name), '_');
                $templatesArray[$templateKey] = [
                    'name' => $name,
                    'title' => $title,
                    'incident_description' => $incidentDescription,
                    'actions_taken' => $actionsTaken,
                ];

            } else {
                 $this->warn("Skipping a template block because its structure is incorrect. Please check for all required markers (NAME:, TITEL:, --- EINSATZHERGANG ---, --- MASSNAHMEN ---).");
            }
        }

        // 4. Create the PHP config file content
        $phpContent = "<?php\n\nreturn " . var_export($templatesArray, true) . ";\n";

        // 5. Write the content to the config file
        $configPath = config_path('report_templates.php');
        file_put_contents($configPath, $phpContent);
        
        $this->info('Successfully imported ' . count($templatesArray) . ' templates.');
        $this->warn('Important: Please run "php artisan config:clear" to apply the changes.');

        return 0;
    }
}

