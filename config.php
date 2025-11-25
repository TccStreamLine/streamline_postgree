<?php

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Dados de conexão extraídos do seu link Neon
$host = 'ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_8E6cCUhIaxAs'; // A senha que estava no seu arquivo

try {
    // --- CORREÇÃO APLICADA ---
    // Conexão simplificada: Removemos o 'options=endpoint' para evitar o erro de SNI
    $dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=require";

    // Cria a conexão PDO
    $pdo = new PDO($dsn, $user, $pass);

    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>