<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';


$r = $_GET['r'] ?? 'dashboard/home';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';


// Carrega controladores
require_once __DIR__.'/controllers/AuthController.php';
require_once __DIR__.'/controllers/DashboardController.php';
require_once __DIR__.'/controllers/EntryController.php';
require_once __DIR__.'/controllers/RateController.php';
require_once __DIR__.'/controllers/SalaryController.php';


switch ($r) {
// Auth
case 'auth/login': $method==='POST' ? auth_login_post($pdo) : auth_login_get(); break;
case 'auth/register':$method==='POST' ? auth_register_post($pdo) : auth_register_get(); break;
case 'auth/logout': auth_logout(); break;


// Dashboard
case 'dashboard/home': require_login(); dashboard_home_get($pdo); break;


// Ações (POST)
case 'entries/save': require_login(); entries_save_post($pdo); break;
case 'rates/save': require_login(); rates_save_post($pdo); break;
case 'salary/sim': require_login(); salary_sim_post(); break; // (no momento, só client-side)


default: http_response_code(404); echo 'Rota não encontrada: '.h($r);
}
?>