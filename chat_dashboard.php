<?php
session_start();
include_once('config.php');

// ===================================================================
// == SEGURANÇA: GERE UMA NOVA CHAVE E COLOQUE AQUI                 ==
// ===================================================================
define('GEMINI_API_KEY', 'AIzaSyDB41H-kbuT8mkN3JP_2DbHAFyZKkZJQJY');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['resposta' => 'Erro: Usuário não autenticado.']);
    exit;
}
$usuario_id = $_SESSION['id'];

try {
    // --- 1. FATURAMENTO DO MÊS ATUAL (POSTGRESQL) ---
    // Mudança: MONTH()/YEAR() -> EXTRACT(... FROM ...)
    // Mudança: CURDATE() -> CURRENT_DATE
    $sql_fat = "SELECT SUM(valor_total) as total 
                FROM vendas 
                WHERE usuario_id = ? 
                AND EXTRACT(MONTH FROM data_venda) = EXTRACT(MONTH FROM CURRENT_DATE) 
                AND EXTRACT(YEAR FROM data_venda) = EXTRACT(YEAR FROM CURRENT_DATE) 
                AND status = 'Finalizada'"; // Cuidado com Case Sensitive no 'Finalizada'
    
    $faturamento_mes_stmt = $pdo->prepare($sql_fat);
    $faturamento_mes_stmt->execute([$usuario_id]);
    $faturamento_mes = $faturamento_mes_stmt->fetchColumn();

    // --- 2. VENDAS DE HOJE (POSTGRESQL) ---
    // Mudança: DATE(data) -> data::DATE
    $sql_hoje = "SELECT COUNT(id) as total 
                 FROM vendas 
                 WHERE usuario_id = ? 
                 AND data_venda::DATE = CURRENT_DATE 
                 AND status = 'Finalizada'";

    $vendas_hoje_stmt = $pdo->prepare($sql_hoje);
    $vendas_hoje_stmt->execute([$usuario_id]);
    $vendas_hoje = $vendas_hoje_stmt->fetchColumn();

    // --- 3. ESTOQUE BAIXO ---
    // Essa query é padrão SQL, funciona igual.
    $estoque_baixo_stmt = $pdo->prepare("SELECT COUNT(id) as total FROM produtos WHERE quantidade_estoque <= quantidade_minima");
    $estoque_baixo_stmt->execute();
    $estoque_baixo = $estoque_baixo_stmt->fetchColumn();

    // --- 4. TOP 5 PRODUTOS ---
    // Essa query também é compatível, mas verifique se 'status' está escrito igual no banco ('Finalizada' vs 'finalizada')
    $top_produtos_stmt = $pdo->prepare("
        SELECT p.nome, SUM(vi.quantidade) as total_vendido 
        FROM venda_itens vi 
        JOIN produtos p ON vi.produto_id = p.id 
        JOIN vendas v ON vi.venda_id = v.id 
        WHERE v.usuario_id = ? AND v.status = 'Finalizada' 
        GROUP BY p.nome 
        ORDER BY total_vendido DESC 
        LIMIT 5
    ");
    $top_produtos_stmt->execute([$usuario_id]);
    $top_produtos = $top_produtos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 5. PRODUTO CAMPEÃO ---
    $produto_campeao_stmt = $pdo->prepare("
        SELECT p.nome 
        FROM venda_itens vi 
        JOIN produtos p ON vi.produto_id = p.id 
        JOIN vendas v ON vi.venda_id = v.id 
        WHERE v.usuario_id = ? AND v.status = 'Finalizada' 
        GROUP BY p.nome 
        ORDER BY SUM(vi.quantidade) DESC 
        LIMIT 1
    ");
    $produto_campeao_stmt->execute([$usuario_id]);
    $produto_campeao = $produto_campeao_stmt->fetchColumn();

} catch (PDOException $e) {
    // Em caso de erro SQL, retorna um JSON para não quebrar o chat
    echo json_encode(['resposta' => 'Erro interno ao analisar dados: ' . $e->getMessage()]);
    exit;
}

// --- PREPARAÇÃO DO CONTEXTO PARA A IA ---
$dados_para_ia = [
    'faturamento_mes_atual' => (float) $faturamento_mes,
    'vendas_hoje' => (int) $vendas_hoje,
    'produtos_com_estoque_baixo' => (int) $estoque_baixo,
    'produto_mais_vendido' => $produto_campeao ?: 'Nenhum',
    'top_5_produtos_mais_vendidos' => $top_produtos
];
$contexto_json = json_encode($dados_para_ia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// --- RECEBENDO A PERGUNTA DO USUÁRIO ---
$json_input = file_get_contents('php://input');
$data_input = json_decode($json_input);
$pergunta_usuario = $data_input->pergunta ?? '';

if (empty($pergunta_usuario)) {
    header('Content-Type: application/json');
    echo json_encode(['resposta' => 'Por favor, faça uma pergunta.']);
    exit;
}

// --- MONTAGEM DO PROMPT ---
$prompt = "Você é 'Relp!', um assistente de IA amigável e especialista em análise de dados de negócios. Sua tarefa é responder à pergunta do usuário de forma clara e objetiva, baseando-se estritamente nos dados fornecidos no contexto JSON abaixo. Não invente informações. Se a resposta não estiver nos dados, diga que você não tem essa informação.

Contexto dos dados da empresa:
{$contexto_json}

Pergunta do usuário:
\"{$pergunta_usuario}\"

Sua resposta:";

// --- COMUNICAÇÃO COM A API DO GEMINI ---
// (Mantida igual, pois é curl padrão)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . GEMINI_API_KEY;

$data_payload = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// --- PROCESSAMENTO DA RESPOSTA ---
header('Content-Type: application/json');
if ($httpcode == 200) {
    $result = json_decode($response, true);
    $texto_da_ia = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpe, não consegui processar sua pergunta no momento.';
    echo json_encode(['resposta' => $texto_da_ia]);
} else {
    // Log de erro mais detalhado pode ser útil no console do navegador
    echo json_encode(['resposta' => 'Erro ao contatar a IA. Código: ' . $httpcode]);
}
?>