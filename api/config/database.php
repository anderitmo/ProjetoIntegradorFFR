<?php
// Configurações do Banco de Dados usando PDO
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'inhosti_projint';
$username = 'inhosti_upi07';
$password = 'WRj85xS1uZAq';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
