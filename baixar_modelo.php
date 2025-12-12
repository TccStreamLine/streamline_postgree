<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=modelo_importacao_completo.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); 

$cabecalho = [
    'Codigo de Barras', 
    'Nome do Produto', 
    'Estoque', 
    'Minimo', 
    'Custo (R$)', 
    'Venda (R$)', 
    'Descricao',
    'Categoria (Nome)',   
    'Fornecedor (Nome)'   
];

fputcsv($output, $cabecalho, ';');

$exemplo = [
    '789123456', 
    'Detergente Neutro 500ml', 
    '100', 
    '20', 
    '1,50', 
    '3,00', 
    'Produto de Limpeza',
    'Limpeza',            
    'Atacadão XYZ'        
];
fputcsv($output, $exemplo, ';');

fclose($output);
exit;
?>