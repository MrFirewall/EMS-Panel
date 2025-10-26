<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Standard-Name für die 'roles'-Tabelle von Spatie holen
        $roleModel = new Role();
        $rolesTable = $roleModel->getTable();
        $rolesKeyName = $roleModel->getKeyName();

        Schema::create('department_role', function (Blueprint $table) use ($rolesTable, $rolesKeyName) {
            
            $table->foreignId('department_id')
                ->constrained('departments')
                ->onDelete('cascade');
            
            // Hier verknüpfen wir mit der Spatie 'roles'-Tabelle
            $table->foreignIdFor(Role::class, 'role_id')
                ->constrained($rolesTable)
                ->onDelete('cascade');

            $table->primary(['department_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_role_pivot');
    }
};
