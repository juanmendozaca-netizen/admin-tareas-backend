<?php

use Illuminate\Database\Migrations\Migration; //toda migracion hereda esta clase
use Illuminate\Database\Schema\Blueprint; //clase que dibuja la estructura de la tabla 
use Illuminate\Support\Facades\Schema; //este ejecuta las ordenes(crear, eliminar, editar, etc) en la base de datos

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users'); //elimina la tabla si en caso existe.
    }
};
