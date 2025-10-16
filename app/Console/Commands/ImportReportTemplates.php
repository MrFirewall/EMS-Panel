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
        // --- ERWEITERTES DEBUGGING ---
        $this->info("--- Starting Advanced Debug Check ---");
        $absolutePath = storage_path('app/templates/vorlagen.txt');
        $this->line("1. Laravel expects the file at this absolute path:");
        $this->comment($absolutePath);

        if (file_exists($absolutePath)) {
            $this->info("2. PHP Check: SUCCESS! The file was found by PHP directly.");
        } else {
            $this->error("2. PHP Check: FAILED! PHP cannot find the file at that path.");
            $this->error("   This indicates a server-level issue (permissions, open_basedir) or an incorrect path.");
            return 1;
        }

        if (is_readable($absolutePath)) {
            $this->info("3. Permission Check: SUCCESS! The file is readable by the PHP process.");
        } else {
            $this->error("3. Permission Check: FAILED! The file was found, but PHP cannot read it.");
             $this->error("   Please re-run the 'chown' and 'chmod' commands.");
            return 1;
        }
        $this->info("--- Advanced Debug Check Complete ---");
        // --- ENDE DEBUGGING ---


        $this->info('Starting report template import...');

        // 1. Read the user-friendly text file
        if (!Storage::exists('templates/vorlagen.txt')) {
            $this->error('Error: Laravel Storage facade still cannot find storage/app/templates/vorlagen.txt.');
            $this->error('This confirms the issue is likely within your config/filesystems.php file.');
            return 1;
        }
        $content = Storage::get('templates/vorlagen.txt');

        // 2. Parse the content
        $templatesArray = [];
        $processedHashes = []; 
        $templateBlocks = explode('[NEUE VORLAGE]', $content);

        foreach ($templateBlocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            preg_match('/NAME:(.*)/', $block, $nameMatches);
            preg_match('/TITEL:(.*)/', $block, $titleMatches);
            
            $name = trim($nameMatches[1] ?? '');
            $title = trim($titleMatches[1] ?? '');

            if (empty($name) || empty($title)) {
                $this->warn("Skipping a template block because Name or Title is missing.");
                continue;
            }

            $incidentParts = explode('--- EINSATZHERGANG ---', $block);
            $incidentAndActions = $incidentParts[1] ?? '';
            $actionParts = explode('--- MASSNAHMEN ---', $incidentAndActions);
            $incidentDescription = trim($actionParts[0] ?? '');
            $actionsTaken = trim($actionParts[1] ?? '');

            $contentSignature = $name . $title . $incidentDescription . $actionsTaken;
            $contentHash = md5($contentSignature);

            if (in_array($contentHash, $processedHashes)) {
                $this->warn("Skipping duplicate template content found with name '{$name}'.");
                continue;
            }
            $processedHashes[] = $contentHash;

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

