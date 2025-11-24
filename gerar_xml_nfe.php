<?php
session_start();
include_once('config.php');

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['id'];
$venda_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$venda_id) {
    die("ID da venda inválido.");
}

try {
    // Query padrão, funciona em MySQL e Postgres
    $stmt_venda = $pdo->prepare("SELECT v.*, u.nome_empresa as emit_nome, u.cnpj as emit_cnpj FROM vendas v JOIN usuarios u ON v.usuario_id = u.id WHERE v.id = ? AND v.usuario_id = ?");
    $stmt_venda->execute([$venda_id, $usuario_id]);
    $venda = $stmt_venda->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        die("Venda não encontrada ou não pertence a este usuário.");
    }

    // UNION ALL padrão. No Postgres, NULL é compatível com VARCHAR, então sem problemas.
    $stmt_itens = $pdo->prepare("
        SELECT 'produto' as tipo, p.nome, vi.quantidade, vi.valor_unitario, p.codigo_barras
        FROM venda_itens vi
        JOIN produtos p ON vi.produto_id = p.id
        WHERE vi.venda_id = ?
        UNION ALL
        SELECT 'servico' as tipo, s.nome_servico as nome, 1 as quantidade, vs.valor as valor_unitario, NULL as codigo_barras
        FROM venda_servicos vs
        JOIN servicos_prestados s ON vs.servico_id = s.id
        WHERE vs.venda_id = ?
    ");
    $stmt_itens->execute([$venda_id, $venda_id]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

    // Geração do XML (Lógica PHP pura, independente do banco)
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><NFe xmlns="http://www.portalfiscal.inf.br/nfe"></NFe>');

    $infNFe = $xml->addChild('infNFe');
    $infNFe->addAttribute('versao', '4.00');
    $infNFe->addAttribute('Id', 'NFe' . str_pad($venda_id, 9, '0', STR_PAD_LEFT));

    $ide = $infNFe->addChild('ide');
    $ide->addChild('cUF', '35');
    $ide->addChild('cNF', str_pad((string)$venda_id, 8, '0', STR_PAD_LEFT));
    $ide->addChild('natOp', 'Venda de mercadoria');
    $ide->addChild('mod', '55');
    $ide->addChild('serie', '1');
    $ide->addChild('nNF', $venda_id);
    $ide->addChild('dhEmi', date('c', strtotime($venda['data_venda'])));
    $ide->addChild('tpNF', '1');
    $ide->addChild('idDest', '1');
    $ide->addChild('cMunFG', '3550308');
    $ide->addChild('tpImp', '1');
    $ide->addChild('tpEmis', '1');
    $ide->addChild('cDV', '0');
    $ide->addChild('tpAmb', '2');
    $ide->addChild('finNFe', '1');
    $ide->addChild('indFinal', '1');
    $ide->addChild('indPres', '1');
    $ide->addChild('procEmi', '0');
    $ide->addChild('verProc', 'Streamline 1.0');

    $emit = $infNFe->addChild('emit');
    $emit->addChild('CNPJ', $venda['emit_cnpj']);
    $emit->addChild('xNome', $venda['emit_nome']);
    $enderEmit = $emit->addChild('enderEmit');
    $enderEmit->addChild('xLgr', 'Rua Exemplo');
    $enderEmit->addChild('nro', '123');
    $enderEmit->addChild('xBairro', 'Centro');
    $enderEmit->addChild('cMun', '3550308');
    $enderEmit->addChild('xMun', 'Sao Paulo');
    $enderEmit->addChild('UF', 'SP');
    $enderEmit->addChild('CEP', '01000000');
    $enderEmit->addChild('cPais', '1058');
    $enderEmit->addChild('xPais', 'Brasil');
    $emit->addChild('IE', 'ISENTO');
    $emit->addChild('CRT', '1');

    $dest = $infNFe->addChild('dest');
    $dest->addChild('CPF', '00000000000');
    $dest->addChild('xNome', 'CLIENTE NAO IDENTIFICADO');
    $enderDest = $dest->addChild('enderDest');
    $enderDest->addChild('xLgr', 'Rua Cliente');
    $enderDest->addChild('nro', '456');
    $enderDest->addChild('xBairro', 'Bairro Cliente');
    $enderDest->addChild('cMun', '3550308');
    $enderDest->addChild('xMun', 'Sao Paulo');
    $enderDest->addChild('UF', 'SP');
    $enderDest->addChild('CEP', '02000000');
    $enderDest->addChild('cPais', '1058');
    $enderDest->addChild('xPais', 'Brasil');
    $dest->addChild('indIEDest', '9');
    $dest->addChild('email', 'cliente@exemplo.com');

    $itemCount = 1;
    foreach ($itens as $item) {
        $det = $infNFe->addChild('det');
        $det->addAttribute('nItem', $itemCount++);

        $prod = $det->addChild('prod');
        $prod->addChild('cProd', $item['tipo'] == 'produto' ? $item['codigo_barras'] ?? $itemCount : 'SERV' . $itemCount);
        $prod->addChild('cEAN', $item['tipo'] == 'produto' ? $item['codigo_barras'] ?? '' : '');
        $prod->addChild('xProd', $item['nome']);
        $prod->addChild('NCM', '00000000');
        $prod->addChild('CFOP', '5102');
        $prod->addChild('uCom', 'UN');
        $prod->addChild('qCom', number_format($item['quantidade'], 4, '.', ''));
        $prod->addChild('vUnCom', number_format($item['valor_unitario'], 10, '.', ''));
        $prod->addChild('vProd', number_format($item['valor_unitario'] * $item['quantidade'], 2, '.', ''));
        $prod->addChild('cEANTrib', $item['tipo'] == 'produto' ? $item['codigo_barras'] ?? '' : '');
        $prod->addChild('uTrib', 'UN');
        $prod->addChild('qTrib', number_format($item['quantidade'], 4, '.', ''));
        $prod->addChild('vUnTrib', number_format($item['valor_unitario'], 10, '.', ''));
        $prod->addChild('indTot', '1');

        $imposto = $det->addChild('imposto');
        $ICMS = $imposto->addChild('ICMS');
        $ICMSSN102 = $ICMS->addChild('ICMSSN102');
        $ICMSSN102->addChild('orig', '0');
        $ICMSSN102->addChild('CSOSN', '102');
        $PIS = $imposto->addChild('PIS');
        $PISOutr = $PIS->addChild('PISOutr');
        $PISOutr->addChild('CST', '99');
        $PISOutr->addChild('vBC', '0.00');
        $PISOutr->addChild('pPIS', '0.00');
        $PISOutr->addChild('vPIS', '0.00');
        $COFINS = $imposto->addChild('COFINS');
        $COFINSOutr = $COFINS->addChild('COFINSOutr');
        $COFINSOutr->addChild('CST', '99');
        $COFINSOutr->addChild('vBC', '0.00');
        $COFINSOutr->addChild('pCOFINS', '0.00');
        $COFINSOutr->addChild('vCOFINS', '0.00');
    }

    $total = $infNFe->addChild('total');
    $ICMSTot = $total->addChild('ICMSTot');
    $ICMSTot->addChild('vBC', '0.00');
    $ICMSTot->addChild('vICMS', '0.00');
    $ICMSTot->addChild('vICMSDeson', '0.00');
    $ICMSTot->addChild('vFCP', '0.00');
    $ICMSTot->addChild('vBCST', '0.00');
    $ICMSTot->addChild('vST', '0.00');
    $ICMSTot->addChild('vFCPST', '0.00');
    $ICMSTot->addChild('vFCPSTRet', '0.00');
    $ICMSTot->addChild('vProd', number_format($venda['valor_total'], 2, '.', ''));
    $ICMSTot->addChild('vFrete', '0.00');
    $ICMSTot->addChild('vSeg', '0.00');
    $ICMSTot->addChild('vDesc', '0.00');
    $ICMSTot->addChild('vII', '0.00');
    $ICMSTot->addChild('vIPI', '0.00');
    $ICMSTot->addChild('vIPIDevol', '0.00');
    $ICMSTot->addChild('vPIS', '0.00');
    $ICMSTot->addChild('vCOFINS', '0.00');
    $ICMSTot->addChild('vOutro', '0.00');
    $ICMSTot->addChild('vNF', number_format($venda['valor_total'], 2, '.', ''));
    $ICMSTot->addChild('vTotTrib', '0.00');

    $transp = $infNFe->addChild('transp');
    $transp->addChild('modFrete', '9');

    $pag = $infNFe->addChild('pag');
    $detPag = $pag->addChild('detPag');
    $detPag->addChild('tPag', '01');
    $detPag->addChild('vPag', number_format($venda['valor_total'], 2, '.', ''));

    $infAdic = $infNFe->addChild('infAdic');
    $infAdic->addChild('infCpl', 'Documento emitido por ME ou EPP optante pelo Simples Nacional.');

    header('Content-type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="nfe_venda_' . $venda_id . '.xml"');

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    echo $dom->saveXML();

} catch (PDOException $e) {
    die("Erro ao buscar dados ou gerar XML: " . $e->getMessage());
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>