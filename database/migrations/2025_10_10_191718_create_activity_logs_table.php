<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // WER: Die ID des Benutzers, der die Aktion ausgeführt hat.
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // WAS: Der Typ des betroffenen Models (z.B. 'User', 'Vacation', 'Role').
            $table->string('log_type', 50);
            
            // WIE: Die ausgeführte Aktion (z.B. 'CREATED', 'UPDATED', 'STATUS_CHANGE').
            $table->string('action', 50);
            
            // WEN/WAS BETROFFEN: ID des betroffenen Eintrags (optional)
            $table->unsignedBigInteger('target_id')->nullable();
            
            // Details: Kurze Beschreibung der Änderung
            $table->text('description');

            // WANN: Zeitstempel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};