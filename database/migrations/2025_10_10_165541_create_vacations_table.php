<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vacations', function (Blueprint $table) {
            $table->id();
            
            // Antragsteller: Foreign Key zur User ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Antragsdaten (zwingend erforderlich)
            $table->date('start_date');
            $table->date('end_date');
            
            // Begründung (Optional)
            $table->text('reason')->nullable(); 

            // Genehmiger (Optional, da initial NULL)
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); 
            
            // Interna (Status und Notizen)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('internal_notes')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacations');
    }
};