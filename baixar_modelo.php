<?php

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=modelo_importacao_produtos.csv');


$output = fopen('php://output', 'w');


fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));


$cabecalho = [
    'Codigo de Barras', 
    'Nome do Produto', 
    'Estoque Atual', 
    'Estoque Minimo', 
    'Valor Compra (R$)', 
    'Valor Venda (R$)', 
    'Descricao/Especificacao'
];


fputcsv($output, $cabecalho, ';');


$exemplo = [
    '7891234567890', 
    'Exemplo: Detergente Neutro 500ml', 
    '100', 
    '20', 
    '1,50', 
    '3,00', 
    'Detergente marca Ype'
];
fputcsv($output, $exemplo, ';');

fclose($output);
exit;
?>