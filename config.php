<?php

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Dados de conexão extraídos do seu link Neon
$host = 'ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_8E6cCUhIaxAs';

try {
    // Monta a string de conexão (DSN) para PostgreSQL com SSL obrigatório
    $dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=require";

    // Cria a conexão PDO
    $pdo = new PDO($dsn, $user, $pass);

    // Configura o PDO para lançar exceções em caso de erro (bom para debugging)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opcional: Teste simples (remova depois)
    // echo "Conexão com Neon realizada com sucesso!";

} catch (PDOException $e) {
    // Em caso de erro, encerra e mostra mensagem (ideal para dev)
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>