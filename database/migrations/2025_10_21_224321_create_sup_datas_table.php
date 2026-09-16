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
        Schema::create('sup_datas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id')->index();
            $table->string('regiao',150)->nullable();
            $table->string('uf',150)->nullable();
            $table->string('cn', 150)->nullable();
            $table->string('cnl_municipio', 150)->nullable();
            $table->string('descricao_municipio', 150)->nullable();
            $table->string('cnl_localidade', 150)->nullable();
            $table->string('descricao_localidade', 150)->nullable();
            $table->string('bairro', 150)->nullable();
            $table->string('codigo_tri',150)->nullable();
            $table->string('codigo_fixa',150)->nullable();
            $table->string('codigo_movel',150)->nullable();
            $table->string('tarifa_fixa',150)->nullable();
            $table->string('tarifa_movel',150)->nullable();
            $table->string('prestadora_chamada', 150)->nullable();
            $table->string('data_ativacao', 150)->nullable();
            $table->string('data_desativacao',150)->nullable();
            $table->string('pessoa_sup', 150)->nullable();
            $table->string('telefone_sup',150)->nullable();
            $table->string('email_sup',150)->nullable();
            $table->string('remuneracao_fixa', 150)->nullable();
            $table->string('nome_sup', 150)->nullable();
            $table->string('remuneracao_movel', 150)->nullable();
            $table->string('prestadora_sup', 150)->nullable();
            $table->string('data_comunicacao', 150)->nullable();
            $table->string('tipo_servico', 150)->nullable();
            
            
            
            $table->timestamps();
            $table->index(['import_id', 'cnl_localidade']);
            $table->index(['import_id', 'cnl_municipio']);
        });
    }
    
    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('sup_datas');
    }
};
