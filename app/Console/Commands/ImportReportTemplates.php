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
        if (!Storage::exists('templates/vorlagen.txt')) {
            $this->error('Error: The source file storage/app/templates/vorlagen.txt was not found.');
            return 1;
        }
        $content = Storage::get('templates/vorlagen.txt');

        // 2. Parse the content
        $templatesArray = [];
        $templateBlocks = explode('[NEUE VORLAGE]', $content);

        foreach ($templateBlocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            // Extract Name and Title
            preg_match('/NAME:(.*)/', $block, $nameMatches);
            preg_match('/TITEL:(.*)/', $block, $titleMatches);
            
            $name = trim($nameMatches[1] ?? '');
            $title = trim($titleMatches[1] ?? '');

            if (empty($name) || empty($title)) {
                $this->warn("Skipping a template block because Name or Title is missing.");
                continue;
            }

            // Extract multiline descriptions
            $incidentParts = explode('--- EINSATZHERGANG ---', $block);
            $incidentAndActions = $incidentParts[1] ?? '';
            
            $actionParts = explode('--- MASSNAHMEN ---', $incidentAndActions);
            
            $incidentDescription = trim($actionParts[0] ?? '');
            $actionsTaken = trim($actionParts[1] ?? '');

            // 3. Build the array structure
            $templateKey = Str::slug(strtolower($name), '_');
            $templatesArray[$templateKey] = [
                'name' => $name,
                'title' => $title,
                'incident_description' => $incidentDescription,
                'actions_taken' => $actionsTaken,
            ];
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
