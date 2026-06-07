<?php
require_once __DIR__ . '/middleware/auth.php';
verificarPermissao('exportar_relatorios');

require_once __DIR__ . '/sustentabilidade-dados.php';

$dados = dadosSustentabilidade();
$nomeFicheiro = 'relatorio_sustentabilidade_' . date('Y_m_d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeFicheiro . '"');
header('Cache-Control: no-store');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF");

fputcsv($saida, ['Relatório de Sustentabilidade PCU - UPT']);
fputcsv($saida, ['Período', $dados['periodo']]);
fputcsv($saida, []);

fputcsv($saida, ['Indicador', 'Valor', 'Descrição']);
foreach ($dados['resumo'] as $indicador) {
    fputcsv($saida, [$indicador['titulo'], $indicador['valor'], $indicador['descricao']]);
}

fputcsv($saida, []);
fputcsv($saida, ['Evolução mensal']);
fputcsv($saida, ['Mês', 'Consumo energético (kWh)', 'Poupança acumulada (kWh)']);
foreach ($dados['evolucao'] as $linha) {
    fputcsv($saida, [$linha['mes'], $linha['consumo_kwh'], $linha['poupanca_kwh']]);
}

fputcsv($saida, []);
fputcsv($saida, ['Indicadores por recurso']);
fputcsv($saida, ['Recurso', 'Energia atual (kWh estimado)']);
foreach ($dados['edificios'] as $edificio) {
    fputcsv($saida, [$edificio['edificio'], $edificio['energia_kwh']]);
}

fputcsv($saida, []);
fputcsv($saida, ['Metas']);
fputcsv($saida, ['Meta', 'Descrição', 'Estado']);
foreach ($dados['metas'] as $meta) {
    fputcsv($saida, [$meta['titulo'], $meta['descricao'], $meta['estado']]);
}

fputcsv($saida, []);
fputcsv($saida, ['Tendência', $dados['tendencia']['estado']]);
fputcsv($saida, ['Descrição', $dados['tendencia']['descricao']]);
fputcsv($saida, ['Projeção anual', $dados['tendencia']['projecao']]);

fclose($saida);
