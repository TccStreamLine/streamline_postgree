<?php

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Dados de conexão extraídos do seu link Neon
$host = 'ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_8E6cCUhIaxAs'; // ATENÇÃO: Use a sua senha real

// Endpoint ID extraído do Host (Necessário para clientes antigos sem SNI)
$endpoint_id = 'ep-damp-sound-ado6c3f7';

try {
    // CORREÇÃO CRÍTICA: Removemos o '-c' e simplificamos as opções para evitar que o servidor rejeite o argumento.
    $dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=require;options=endpoint=$endpoint_id";

    // Cria a conexão PDO
    $pdo = new PDO($dsn, $user, $pass);

    // Configura o PDO para lançar exceções em caso de erro (bom para debugging)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Em caso de erro, encerra e mostra mensagem (ideal para dev)
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>