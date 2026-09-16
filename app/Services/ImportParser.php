<?php
namespace App\Services;

use RuntimeException;

class ImportParser
{
    private const CADUP = [
        'stfc' => ['nome_prestadora', 'cnpj_prestadora', 'codigo_nacional', 'prefixo', 'mcdu_i', 'mcdu_f', 'cnl', 'nome_localidade', 'area_local', 'cod_area_local'],
        'smp' => ['nome_prestadora', 'cnpj_prestadora', 'codigo_nacional', 'prefixo', 'faixa_inicial', 'faixa_final', 'status'],
    ];
    private const SUP = [
        'regiao' => [0, 1], 'uf' => [1, 2], 'cn' => [3, 2], 'cnl_municipio' => [5, 5],
        'descricao_municipio' => [10, 50], 'cnl_localidade' => [60, 5], 'descricao_localidade' => [65, 60],
        'bairro' => [125, 8], 'codigo_tri' => [133, 6], 'codigo_fixa' => [139, 11], 'codigo_movel' => [150, 11],
        'tarifa_fixa' => [161, 1], 'tarifa_movel' => [162, 1], 'prestadora_chamada' => [163, 50],
        'data_ativacao' => [213, 8], 'data_desativacao' => [221, 8], 'pessoa_sup' => [229, 100],
        'telefone_sup' => [329, 10], 'email_sup' => [339, 50], 'remuneracao_fixa' => [389, 1],
        'nome_sup' => [390, 100], 'remuneracao_movel' => [490, 1], 'prestadora_sup' => [491, 50],
        'data_comunicacao' => [541, 8], 'tipo_servico' => [549, 40],
    ];

    public function rows(string $path, string $type): \Generator
    {
        $handle = fopen($path, 'rb');
        if (!$handle) throw new RuntimeException('Arquivo privado indisponível.');
        try {
            $first = fgets($handle);
            if ($first === false) throw new RuntimeException('Arquivo vazio.');
            $encoding = $this->encoding($first);
            rewind($handle);
            if ($type === 'sup') {
                $number = 0;
                while (($line = fgets($handle)) !== false) {
                    $number++;
                    $line = rtrim($line, "\r\n");
                    if ($number === 1 && str_starts_with($line, "\xEF\xBB\xBF")) $line = substr($line, 3);
                    if ($line === '') continue;
                    if (strlen($line) < 589) throw new RuntimeException("Linha $number: SUP menor que 589 bytes após conversão; layout não confirmado.");
                    $row = [];
                    foreach (self::SUP as $field => [$start, $length]) $row[$field] = trim($this->toUtf8(substr($line, $start, $length), $encoding)) ?: null;
                    if (!$row['cnl_localidade'] && !$row['cnl_municipio']) throw new RuntimeException("Linha $number: código de localidade/município ausente.");
                    foreach (['data_ativacao', 'data_desativacao', 'data_comunicacao'] as $field) {
                        if ($row[$field] && !preg_match('/^\d{8}$/D', $row[$field])) throw new RuntimeException("Linha $number: $field precisa ter formato AAAAMMDD.");
                    }
                    yield [$number, $row];
                }
                return;
            }
            $headerLine = $this->toUtf8($first, $encoding);
            $delimiter = $this->delimiter($headerLine);
            $headers = array_map(fn ($v) => trim(mb_strtolower($v)), str_getcsv($headerLine, $delimiter, '"', '\\'));
            $required = self::CADUP[$type];
            if (count($headers) !== count(array_unique($headers)) || array_diff($required, $headers)) {
                throw new RuntimeException('Cabeçalho CADUP incompatível. Colunas exigidas: '.implode(', ', $required));
            }
            fgets($handle); // consumir o cabeçalho após rewind
            $number = 1;
            while (($line = fgets($handle)) !== false) {
                $number++;
                $line = $this->toUtf8($line, $encoding);
                if (trim($line) === '') continue;
                $values = str_getcsv($line, $delimiter, '"', '\\');
                if (count($values) !== count($headers)) throw new RuntimeException("Linha $number: quantidade de colunas diferente do cabeçalho.");
                $all = array_combine($headers, array_map(fn ($v) => trim($v), $values));
                $row = array_intersect_key($all, array_flip($required));
                foreach (['nome_prestadora', 'codigo_nacional', 'prefixo'] as $field) {
                    if (($row[$field] ?? '') === '') throw new RuntimeException("Linha $number: $field obrigatório.");
                }
                $range = $type === 'stfc' ? ['mcdu_i', 'mcdu_f'] : ['faixa_inicial', 'faixa_final'];
                foreach (['codigo_nacional', 'prefixo', ...$range] as $field) {
                    if (!preg_match('/^\d+$/D', $row[$field] ?? '')) throw new RuntimeException("Linha $number: $field precisa conter somente dígitos.");
                }
                if (strlen($row['codigo_nacional']) !== 2 || !in_array(strlen($row['prefixo']), [4, 5], true) || strlen($row[$range[0]]) !== 4 || strlen($row[$range[1]]) !== 4) {
                    throw new RuntimeException("Linha $number: DDD, prefixo ou faixa fora da largura suportada pela consulta.");
                }
                if (strlen($row[$range[0]]) !== strlen($row[$range[1]]) || strcmp($row[$range[0]], $row[$range[1]]) > 0) {
                    throw new RuntimeException("Linha $number: faixa inválida.");
                }
                if ($type === 'stfc') $row['status'] = $all['status'] ?? null;
                yield [$number, $row];
            }
        } finally { fclose($handle); }
    }
    private function encoding(string $sample): string
    {
        if (str_starts_with($sample, "\xEF\xBB\xBF")) return 'UTF-8-BOM';
        if (mb_check_encoding($sample, 'UTF-8')) return 'UTF-8';
        if (mb_check_encoding($sample, 'Windows-1252')) return 'Windows-1252';
        throw new RuntimeException('Encoding não suportado.');
    }
    private function toUtf8(string $line, string $encoding): string
    {
        if ($encoding === 'UTF-8-BOM') return preg_replace('/^\xEF\xBB\xBF/', '', $line);
        if ($encoding === 'Windows-1252') return mb_convert_encoding($line, 'UTF-8', 'Windows-1252');
        if (!mb_check_encoding($line, 'UTF-8')) throw new RuntimeException('Encoding inconsistente entre linhas.');
        return $line;
    }
    private function delimiter(string $line): string
    {
        $candidates = [';', ',', "\t"];
        $counts = array_map(fn ($d) => count(str_getcsv($line, $d, '"', '\\')), $candidates);
        $max = max($counts);
        if ($max < 7 || count(array_keys($counts, $max)) !== 1) throw new RuntimeException('Delimitador não reconhecido sem ambiguidade.');
        return $candidates[array_search($max, $counts, true)];
    }
}
