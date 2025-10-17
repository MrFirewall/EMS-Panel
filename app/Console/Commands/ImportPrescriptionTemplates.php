<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportPrescriptionTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:prescription-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads the prescription templates file and converts it into a config file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting prescription template import...');

        $filePath = 'templates/rezept_vorlagen.txt';
        if (!Storage::exists($filePath)) {
            $this->error('Error: The source file storage/app/templates/rezept_vorlagen.txt was not found.');
            return 1;
        }
        $content = Storage::get($filePath);

        $templatesArray = [];
        $templateBlocks = preg_split('/\[NEUE VORLAGE\]/', $content, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($templateBlocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;

            // Simple pattern to capture Name, Dosage, and Notes
            $pattern = '/^NAME:\s*(.*?)\s*DOSIERUNG:\s*(.*?)\s*(?:HINWEISE:\s*(.*))?$/s';

            if (preg_match($pattern, $block, $matches)) {
                $name = trim($matches[1]);
                $dosage = trim($matches[2]);
                $notes = isset($matches[3]) ? trim($matches[3]) : ''; // Notes are optional

                $templateKey = Str::slug(strtolower($name), '_');
                $templatesArray[$templateKey] = [
                    'name' => $name,
                    'dosage' => $dosage,
                    'notes' => $notes,
                ];
            } else {
                $this->warn("Skipping a template block due to incorrect format. Required: NAME:, DOSIERUNG:. Optional: HINWEISE:");
            }
        }

        $phpContent = "<?php\n\nreturn " . var_export($templatesArray, true) . ";\n";
        $configPath = config_path('prescription_templates.php');
        file_put_contents($configPath, $phpContent);
        
        $this->info('Successfully imported ' . count($templatesArray) . ' prescription templates.');
        $this->warn('Important: Please run "php artisan config:clear" to apply the changes.');

        return 0;
    }
}