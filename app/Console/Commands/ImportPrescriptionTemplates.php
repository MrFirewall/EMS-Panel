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

            // NEU: Muster, um NAME (DE), NAME (EN), DOSIERUNG und HINWEISE zu erfassen
            $name_de = '';
            $name_en = '';
            $dosage = '';
            $notes = '';

            // Zeilenweise Verarbeitung, um die neuen Tags zu erfassen
            $lines = explode("\n", $block);
            foreach ($lines as $line) {
                $line = trim($line);
                if (Str::startsWith($line, 'NAME (DE):')) {
                    $name_de = trim(Str::after($line, 'NAME (DE):'));
                } elseif (Str::startsWith($line, 'NAME (EN):')) {
                    $name_en = trim(Str::after($line, 'NAME (EN):'));
                } elseif (Str::startsWith($line, 'DOSIERUNG:')) {
                    $dosage = trim(Str::after($line, 'DOSIERUNG:'));
                } elseif (Str::startsWith($line, 'HINWEISE:')) {
                    // Erfassen von Hinweisen, die über mehrere Zeilen gehen könnten
                    $notes = trim(Str::after($line, 'HINWEISE:'));
                }
            }
            
            // Logik zur Erfassung mehrzeiliger HINWEISE (fügt nachfolgende Zeilen hinzu)
            if (empty($notes) && !empty($lines)) {
                 $in_notes = false;
                 $temp_notes = [];
                 foreach ($lines as $line) {
                     $line = trim($line);
                     if (Str::startsWith($line, 'HINWEISE:')) {
                         $in_notes = true;
                         $temp_notes[] = trim(Str::after($line, 'HINWEISE:'));
                         continue;
                     }
                     if ($in_notes) {
                         if (Str::startsWith($line, 'NAME (') || Str::startsWith($line, 'DOSIERUNG:')) {
                             $in_notes = false; // Stop at the next field
                         } else {
                             $temp_notes[] = $line;
                         }
                     }
                 }
                 $notes = trim(implode("\n", $temp_notes));
            }


            // Validierung, dass zumindest der DE-Name und die Dosierung vorhanden sind
            if (!empty($name_de) && !empty($dosage)) {
                $templateKey = Str::slug(strtolower($name_de), '_');
                $templatesArray[$templateKey] = [
                    'name_de' => $name_de, // NEU
                    'name_en' => $name_en, // NEU
                    'dosage' => $dosage,
                    'notes' => $notes,
                ];
            } else {
                $this->warn("Skipping a template block due to incorrect format. Missing NAME (DE) or DOSIERUNG. Block started with: " . Str::limit($block, 50));
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