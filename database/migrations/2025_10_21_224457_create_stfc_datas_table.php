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
        Schema::create('stfc_datas', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('import_id')->index();
            
            // Colunas de dados com base nos tipos e NULLABLE
            $table->string('nome_prestadora', 200)->nullable(); 
            $table->string('cnpj_prestadora', 50)->nullable();  
            // Laravel usa integer para int
            $table->string('codigo_nacional', 8)->nullable();
            $table->string('prefixo', 8)->nullable();
            $table->string('mcdu_i', 4)->nullable();             
            $table->string('mcdu_f', 4)->nullable();             
            $table->string('cnl', 8)->nullable();
            $table->string('nome_localidade', 100)->nullable(); 
            $table->string('area_local', 100)->nullable();      
            $table->string('cod_area_local', 16)->nullable();
            $table->string('status', 2)->nullable();             
            
            // Colunas created_at e updated_at
            $table->timestamps();
            $table->index(['import_id', 'codigo_nacional', 'prefixo']);
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('stfc_datas');
    }
};
