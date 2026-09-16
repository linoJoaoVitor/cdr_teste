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
        Schema::create('smp_datas', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('import_id')->index();
            
            // Colunas de dados
            $table->string('nome_prestadora', 200)->nullable(); // varchar(200) DEFAULT NULL
            $table->string('cnpj_prestadora', 50)->nullable();  // varchar(50) DEFAULT NULL
            // Laravel usa integer para int
            $table->string('codigo_nacional', 8)->nullable();
            $table->string('prefixo', 8)->nullable();
            $table->string('faixa_inicial', 4)->nullable();     // varchar(4) DEFAULT NULL
            $table->string('faixa_final', 4)->nullable();       // varchar(4) DEFAULT NULL
            $table->string('status', 2)->nullable();             // varchar(2) DEFAULT NULL
            
            $table->timestamps(); // Colunas created_at e updated_at
            $table->index(['import_id', 'codigo_nacional', 'prefixo']);
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('smp_datas');
    }
};
