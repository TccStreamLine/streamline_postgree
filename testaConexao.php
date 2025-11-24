<?php
// ATENÇÃO: Substitua pelos SEUS dados REAIS do Neon
// Para facilitar, usei os dados do config.php que corrigimos anteriormente.
$host = 'ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech'; 
$db = 'neondb';                                                        
$user = 'neondb_owner';                                                 
$pass = 'npg_8E6cCUhIaxAs';                                         

try {
    // ALTERAÇÃO CRÍTICA: DSN para PostgreSQL (pgsql) e adição de SSL (sslmode=require)
    $dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conexão com Neon (PostgreSQL) funcionando com sucesso!";
} catch (PDOException $e) {
    // Se der erro, verifique se o pdo_pgsql está habilitado no php.ini
    echo "Erro na conexão com Neon (PostgreSQL): " . $e->getMessage();
}
?>