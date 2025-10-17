<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->string('blood_type')->nullable()->after('address'); // Blutgruppe
            $table->text('allergies')->nullable()->after('blood_type'); // Allergien
            $table->text('preexisting_conditions')->nullable()->after('allergies'); // Vorerkrankungen
            $table->string('emergency_contact_name')->nullable()->after('preexisting_conditions'); // Notfallkontakt Name
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name'); // Notfallkontakt Telefon
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            //
        });
    }
};
