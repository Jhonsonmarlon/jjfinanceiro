<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
ini_set('session.use_only_cookies','1');


// --- Conexão ao Neon (DATABASE_URL / POSTGRES_URL)
function pg_dsn_from_url(string $url): array {
$p = parse_url($url);
if (!$p || !isset($p['scheme']) || !str_starts_with($p['scheme'], 'postgres')) {
throw new RuntimeException('DATABASE_URL/POSTGRES_URL inválida.');
}
$host = $p['host'] ?? 'localhost';
$port = (string)($p['port'] ?? '5432');
$user = urldecode($p['user'] ?? '');
$pass = urldecode($p['pass'] ?? '');
$db = ltrim($p['path'] ?? '', '/');
$dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
return [$dsn, $user, $pass];
}

$PGURL = $_ENV['DATABASE_URL']
?? $_ENV['POSTGRES_URL']
?? $_ENV['POSTGRES_PRISMA_URL']
?? $_ENV['POSTGRES_URL_NON_POOLING']
?? getenv('DATABASE_URL')
?? getenv('POSTGRES_URL')
?? '';
if (!$PGURL) { http_response_code(500); echo 'Falta DATABASE_URL.'; exit; }


[$DSN,$DB_USER,$DB_PASS] = pg_dsn_from_url($PGURL);
$pdo = new PDO($DSN, $DB_USER, $DB_PASS, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);


// --- Session handler em tabela `sessions`
class PdoSessionHandler implements SessionHandlerInterface {
public function __construct(private PDO $pdo) {}
public function open($s,$n): bool { return true; }
public function close(): bool { return true; }
public function read($id): string|false {
$st=$this->pdo->prepare('SELECT data FROM sessions WHERE id=:i AND expires>:t');
$st->execute([':i'=>$id,':t'=>time()]);
$r=$st->fetch(); return $r ? (string)$r['data'] : '';
}
public function write($id,$data): bool {
$exp=time()+(int)ini_get('session.gc_maxlifetime');
$st=$this->pdo->prepare('INSERT INTO sessions(id,data,expires) VALUES(:i,:d,:e) ON CONFLICT (id) DO UPDATE SET data=EXCLUDED.data, expires=EXCLUDED.expires');
return $st->execute([':i'=>$id,':d'=>$data,':e'=>$exp]);
}
public function destroy($id): bool { return $this->pdo->prepare('DELETE FROM sessions WHERE id=:i')->execute([':i'=>$id]); }
public function gc($l): int|false { $st=$this->pdo->prepare('DELETE FROM sessions WHERE expires<:t'); $st->execute([':t'=>time()]); return $st->rowCount(); }
}
session_set_save_handler(new PdoSessionHandler($pdo), true);
session_start();

// --- Helpers
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES,'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_ok(): bool { return isset($_POST['csrf']) && hash_equals($_SESSION['csrf']??'', $_POST['csrf']); }
function flash(string $msg,string $type='success'): void { $_SESSION['flash']=[$msg,$type]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function require_login(): void { if (empty($_SESSION['uid'])) { header('Location: ?r=auth/login'); exit; } }


// --- Renderização simples (views PHP)
function render(string $view, array $params=[]): void {
extract($params, EXTR_SKIP);
$flash = get_flash();
$viewFile = __DIR__.'/views/'.$view.'.php';
if (!is_file($viewFile)) { http_response_code(404); echo 'View não encontrada: '.h($view); return; }
ob_start();
include $viewFile; // gera $content
$content = ob_get_clean();
include __DIR__.'/views/layout.php';
}

function nav_html(string $active): string {
$items = ['dashboard/home'=>'Painel','auth/logout'=>'Sair'];
$links='';
foreach ($items as $route=>$label) {
$cls = ($active===$route)?'active':'';
$links.='<a class="'.$cls.'" href="?r='.$route.'">'.h($label).'</a>';
}
return '<div class="nav">'.$links.'</div>';
}

// Seeds de câmbio (garante EUR/BRL do dia)
$pdo->exec("INSERT INTO currency_rates(currency_code,rate_eur,rate_date) VALUES ('EUR',1.00000000,CURRENT_DATE) ON CONFLICT (currency_code,rate_date) DO NOTHING");
$pdo->exec("INSERT INTO currency_rates(currency_code,rate_eur,rate_date) VALUES ('BRL',0.18000000,CURRENT_DATE) ON CONFLICT (currency_code,rate_date) DO NOTHING");


?>