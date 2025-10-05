<?php
declare(strict_types=1);

/**
 * JJFinanceiro – MVP Luso-Brasileiro (Vercel + MySQL Hostinger)
 * PHP 8.1+, MariaDB/MySQL 10+
 * Sessões persistidas no DB para funcionar no serverless.
 */

/////////////////////
// BOOTSTRAP
/////////////////////
ini_set('display_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');

$DB_DSN  = $_ENV['DB_DSN']  ?? getenv('DB_DSN')  ?? 'mysql:host=127.0.0.1;port=3306;dbname=luso_finance;charset=utf8mb4';
$DB_USER = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$DB_PASS = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

try {
  $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo '<h1>Erro de conexão ao banco</h1><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
  exit;
}

/**
 * Session handler em PDO (tabela `sessions` será criada se não existir)
 */
class PdoSessionHandler implements SessionHandlerInterface {
  private PDO $pdo;
  public function __construct(PDO $pdo) { $this->pdo = $pdo; $this->ensureTable(); }
  private function ensureTable(): void {
    $this->pdo->exec("
      CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        data LONGBLOB NOT NULL,
        expires INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (expires)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
  }
  public function open($savePath, $sessionName): bool { return true; }
  public function close(): bool { return true; }
  public function read($id): string|false {
    $st = $this->pdo->prepare("SELECT data FROM sessions WHERE id=? AND expires > ?");
    $st->execute([$id, time()]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (string)$row['data'] : '';
  }
  public function write($id, $data): bool {
    $exp = time() + (int)ini_get('session.gc_maxlifetime');
    $st = $this->pdo->prepare("
      INSERT INTO sessions (id, data, expires) VALUES(?,?,?)
      ON DUPLICATE KEY UPDATE data=VALUES(data), expires=VALUES(expires)
    ");
    return $st->execute([$id, $data, $exp]);
  }
  public function destroy($id): bool {
    $st = $this->pdo->prepare("DELETE FROM sessions WHERE id=?");
    return $st->execute([$id]);
  }
  public function gc($max_lifetime): int|false {
    $st = $this->pdo->prepare("DELETE FROM sessions WHERE expires < ?");
    $st->execute([time()]);
    return $st->rowCount();
  }
}
session_set_save_handler(new PdoSessionHandler($pdo), true);
session_start();

/////////////////////
// HELPERS
/////////////////////
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_ok(): bool { return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']); }
function flash(string $msg, string $type='success'): void { $_SESSION['flash'] = [$msg,$type]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function require_login(): void { if (empty($_SESSION['uid'])) { header('Location: ?page=login'); exit; } }
function current_user(PDO $pdo): ?array {
  if (empty($_SESSION['uid'])) return null;
  $st = $pdo->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$_SESSION['uid']]);
  return $st->fetch() ?: null;
}

/////////////////////
// ROTAS POST
/////////////////////
$page = $_GET['page'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($page === 'register' && csrf_ok()) {
    $name = trim($_POST['name'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $pass = (string)($_POST['pass'] ?? '');
    if (!$name || !$email || strlen($pass) < 6) {
      flash('Preencha nome, email e senha (6+).','danger'); header('Location:?page=register'); exit;
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    try {
      $st=$pdo->prepare('INSERT INTO users(name,email,pass_hash) VALUES(?,?,?)');
      $st->execute([$name,$email,$hash]);
      flash('Cadastro concluído. Faça login.'); header('Location:?page=login'); exit;
    } catch(PDOException $e){ flash('Email já cadastrado.','danger'); header('Location:?page=register'); exit; }
  }

  if ($page === 'login' && csrf_ok()) {
    $email=trim($_POST['email'] ?? ''); $pass=(string)($_POST['pass'] ?? '');
    $st=$pdo->prepare('SELECT * FROM users WHERE email=?'); $st->execute([$email]); $u=$st->fetch();
    if ($u && password_verify($pass, $u['pass_hash'])) { $_SESSION['uid'] = $u['id']; flash('Bem-vindo, '.$u['name'].'!'); header('Location:?page=dashboard'); exit; }
    flash('Credenciais inválidas.','danger'); header('Location:?page=login'); exit;
  }

  if ($page === 'logout') { session_destroy(); header('Location:?page=login'); exit; }

  if ($page === 'entry_save' && csrf_ok()) {
    require_login(); $uid = (int)$_SESSION['uid'];
    $kind = $_POST['kind'] ?? 'expense';
    $category = trim($_POST['category'] ?? 'Geral');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $currency = strtoupper(trim($_POST['currency'] ?? 'EUR'));
    $account = trim($_POST['account'] ?? '');
    $date = $_POST['entry_date'] ?? date('Y-m-d');

    // pega taxa do dia (ou última disponível)
    $st=$pdo->prepare('SELECT rate_eur FROM currency_rates WHERE currency_code=? AND rate_date=?');
    $st->execute([$currency,$date]);
    $rate = $st->fetchColumn();
    if ($rate === false) {
      $st=$pdo->prepare('SELECT rate_eur FROM currency_rates WHERE currency_code=? ORDER BY rate_date DESC LIMIT 1');
      $st->execute([$currency]);
      $rate = $st->fetchColumn();
      if ($rate === false) $rate = 1.0;
    }
    $amount_eur = round($amount * (float)$rate, 2);

    $st=$pdo->prepare('INSERT INTO entries(user_id,kind,category,description,amount,currency_code,fx_rate_eur,amount_eur,account,entry_date) VALUES(?,?,?,?,?,?,?,?,?,?)');
    $st->execute([$uid,$kind,$category,$description,$amount,$currency,$rate,$amount_eur,$account,$date]);

    flash('Lançamento registado.'); header('Location:?page=entries'); exit;
  }

  if ($page==='rate_save' && csrf_ok()) {
    require_login();
    $code=strtoupper(trim($_POST['currency_code'] ?? ''));
    $rate=(float)($_POST['rate_eur'] ?? 0);
    $date=$_POST['rate_date'] ?? date('Y-m-d');
    if (!$code || $rate <= 0) { flash('Informe moeda e taxa válida.','danger'); header('Location:?page=rates'); exit; }
    $st=$pdo->prepare('INSERT INTO currency_rates(currency_code,rate_eur,rate_date) VALUES(?,?,?) ON DUPLICATE KEY UPDATE rate_eur=VALUES(rate_eur)');
    $st->execute([$code,$rate,$date]);
    flash('Taxa de câmbio atualizada.'); header('Location:?page=rates'); exit;
  }

  if ($page==='worklog_save' && csrf_ok()) {
    require_login(); $uid=(int)$_SESSION['uid'];
    $work_date=$_POST['work_date'] ?? date('Y-m-d');
    $hours=(float)($_POST['hours_worked'] ?? 0);
    $ot=(float)($_POST['overtime_hours'] ?? 0);
    $notes=trim($_POST['notes'] ?? '');
    $st=$pdo->prepare('INSERT INTO work_logs(user_id,work_date,hours_worked,overtime_hours,notes) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE hours_worked=VALUES(hours_worked), overtime_hours=VALUES(overtime_hours), notes=VALUES(notes)');
    $st->execute([$uid,$work_date,$hours,$ot,$notes]);
    flash('Registo de horas salvo.'); header('Location:?page=salary'); exit;
  }

  if ($page==='salarycfg_save' && csrf_ok()) {
    require_login(); $uid=(int)$_SESSION['uid'];
    $rate=(float)($_POST['base_hour_rate'] ?? 0);
    $meal=(float)($_POST['meal_allowance_per_day'] ?? 0);
    $mult=(float)($_POST['overtime_multiplier'] ?? 1.25);
    $st=$pdo->prepare('INSERT INTO salary_config(user_id,base_hour_rate,meal_allowance_per_day,overtime_multiplier) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE base_hour_rate=VALUES(base_hour_rate), meal_allowance_per_day=VALUES(meal_allowance_per_day), overtime_multiplier=VALUES(overtime_multiplier)');
    $st->execute([$uid,$rate,$meal,$mult]);
    flash('Configuração salarial atualizada.'); header('Location:?page=salary'); exit;
  }
}

/////////////////////
// VIEWS + LAYOUT
/////////////////////
function layout(string $title, string $contentHtml): void {
  $flash = get_flash();
  echo '<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>'.h($title).'</title><link rel="stylesheet" href="/styles.css"></head><body>';
  echo '<header class="header"><span class="flag pt"></span><span class="flag br"></span><div style="font-weight:800">Finanças 🇵🇹 x 🇧🇷</div></header>';
  if ($flash) echo '<div style="max-width:1080px;margin:16px auto"><div style="background:#102a16;border:1px solid #1f5f30;padding:12px;border-radius:12px;color:#d1fae5">'.h($flash[0]).'</div></div>';
  echo '<div class="container">'.$contentHtml.'</div></body></html>';
}

function nav(string $active): string {
  $items = ['dashboard'=>'Painel','entries'=>'Lançamentos','rates'=>'Câmbio','salary'=>'Ordenado (PT)'];
  $links='';
  foreach ($items as $k=>$v) {
    $cls = $k===$active ? 'active' : '';
    $links .= '<a class="'.$cls.'" href="?page='.$k.'" style="color:#cbd5e1;text-decoration:none;margin-right:10px;border:1px solid #243056;padding:8px 12px;border-radius:10px">'.h($v).'</a>';
  }
  $auth = empty($_SESSION['uid']) ? '<a href="?page=login">Entrar</a>' : '<a href="?page=logout" onclick="return confirm(\'Sair?\')">Sair</a>';
  return '<div class="nav">'.$links.'<span style="flex:1"></span>'.$auth.'</div>';
}

if (($page ?? '') === 'login') {
  $html = '<div class="card" style="max-width:520px;margin:32px auto">'
    .'<h2>Entrar</h2>'
    .'<form method="post"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<label>Email<br><input class="input" type="email" name="email" required></label><br><br>'
    .'<label>Senha<br><input class="input" type="password" name="pass" required></label><br><br>'
    .'<button class="button success" type="submit">Entrar</button> <a class="button" href="?page=register">Criar conta</a>'
    .'</form></div>';
  layout('Login',$html); exit;
}

if (($page ?? '') === 'register') {
  $html = '<div class="card" style="max-width:620px;margin:32px auto">'
    .'<h2>Criar conta</h2>'
    .'<form method="post"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">'
    .'<label>Nome<br><input class="input" type="text" name="name" required></label>'
    .'<label>Email<br><input class="input" type="email" name="email" required></label>'
    .'</div><br>'
    .'<label>Senha (mín. 6)<br><input class="input" type="password" name="pass" required></label><br>'
    .'<button class="button primary" type="submit">Criar</button> <a class="button" href="?page=login">Já tenho conta</a>'
    .'</form></div>';
  layout('Cadastro',$html); exit;
}

require_login();
$u = current_user($pdo);

if ($page === 'dashboard') {
  $month = $_GET['m'] ?? date('Y-m');
  $st=$pdo->prepare("SELECT kind, SUM(amount_eur) v FROM entries WHERE user_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=? GROUP BY kind");
  $st->execute([$u['id'],$month]);
  $sum=['income'=>0,'expense'=>0,'transfer'=>0]; foreach($st as $r){ $sum[$r['kind']] = (float)$r['v']; }
  $balance = $sum['income'] - $sum['expense'];

  $recent = $pdo->prepare("SELECT entry_date,kind,category,description,amount,currency_code,amount_eur FROM entries WHERE user_id=? ORDER BY entry_date DESC, id DESC LIMIT 10");
  $recent->execute([$u['id']]);
  $rows=''; foreach($recent as $r){
    $rows.='<tr>'
      .'<td>'.h($r['entry_date']).'</td>'
      .'<td><span class="badge">'.h($r['kind']).'</span></td>'
      .'<td>'.h($r['category']).'</td>'
      .'<td>'.h($r['description']??'').'</td>'
      .'<td>'.number_format((float)$r['amount'],2,',','.').' '.h($r['currency_code']).'</td>'
      .'<td>'.number_format((float)$r['amount_eur'],2,',','.').' EUR</td>'
      .'</tr>';
  }

  $kpis = '<div class="card"><div class="muted">Receitas (EUR)</div><div class="kpi">'.number_format($sum['income'],2,',','.').'</div></div>'
         .'<div class="card"><div class="muted">Despesas (EUR)</div><div class="kpi">'.number_format($sum['expense'],2,',','.').'</div></div>'
         .'<div class="card"><div class="muted">Balanço (EUR)</div><div class="kpi">'.number_format($balance,2,',','.').'</div></div>';

  $html = nav('dashboard')
    .'<br><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">'.$kpis.'</div><br>'
    .'<div class="card"><h3>Últimos lançamentos</h3>'
    .'<table class="table"><thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Descrição</th><th>Valor</th><th>EUR</th></tr></thead><tbody>'.$rows.'</tbody></table></div>';
  layout('Painel',$html); exit;
}

if ($page === 'entries') {
  $st=$pdo->prepare("SELECT * FROM entries WHERE user_id=? ORDER BY entry_date DESC, id DESC LIMIT 100");
  $st->execute([$u['id']]);
  $rows=''; foreach($st as $r){
    $rows.='<tr>'
      .'<td>'.h($r['entry_date']).'</td>'
      .'<td>'.h($r['kind']).'</td>'
      .'<td>'.h($r['category']).'</td>'
      .'<td>'.h($r['account']).'</td>'
      .'<td>'.number_format((float)$r['amount'],2,',','.').' '.h($r['currency_code']).'</td>'
      .'<td>'.number_format((float)$r['amount_eur'],2,',','.').' EUR</td>'
      .'<td>'.h($r['description']??'').'</td>'
      .'</tr>';
  }

  $form = '<form method="post" action="?page=entry_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">'
    .'<label>Tipo<br><select name="kind"><option value="expense">Despesa</option><option value="income">Receita</option><option value="transfer">Transferência</option></select></label>'
    .'<label>Categoria<br><input class="input" name="category" placeholder="Mercado, Renda, Gasolina..."></label>'
    .'<label>Conta<br><input class="input" name="account" placeholder="PT-IBAN, Nubank, Itaú..."></label>'
    .'</div><br><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">'
    .'<label>Valor<br><input class="input" type="number" step="0.01" name="amount" required></label>'
    .'<label>Moeda<br><input class="input" name="currency" value="EUR" maxlength="3"></label>'
    .'<label>Data<br><input class="input" type="date" name="entry_date" value="'.date('Y-m-d').'"></label>'
    .'</div><br><label>Descrição<br><input class="input" name="description" placeholder="Detalhes"></label><br>'
    .'<button class="button success" type="submit">Adicionar</button></form>';

  $html = nav('entries')
    .'<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">'
    .'<div class="card"><h3>Novo lançamento</h3>'.$form.'</div>'
    .'<div class="card"><h3>Últimos 100</h3><table class="table"><thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Conta</th><th>Valor</th><th>EUR</th><th>Descrição</th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
    .'</div>';
  layout('Lançamentos',$html); exit;
}

if ($page === 'rates') {
  $st=$pdo->query("SELECT currency_code, rate_eur, rate_date FROM currency_rates ORDER BY rate_date DESC, currency_code");
  $rows=''; foreach($st as $r){ $rows.='<tr><td>'.h($r['currency_code']).'</td><td>'.number_format((float)$r['rate_eur'],6,',','.').'</td><td>'.h($r['rate_date']).'</td></tr>'; }
  $form = '<form method="post" action="?page=rate_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">'
    .'<label>Moeda (BRL, EUR...)<br><input class="input" name="currency_code" maxlength="3" required></label>'
    .'<label>Taxa → EUR<br><input class="input" type="number" step="0.00000001" name="rate_eur" required></label>'
    .'<label>Data<br><input class="input" type="date" name="rate_date" value="'.date('Y-m-d').'"></label>'
    .'</div><br><button class="button primary">Guardar/Atualizar</button></form>';

  $html = nav('rates')
    .'<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">'
    .'<div class="card"><h3>Atualizar taxa</h3>'.$form.'</div>'
    .'<div class="card"><h3>Histórico</h3><table class="table"><thead><tr><th>Moeda</th><th>Taxa→EUR</th><th>Data</th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
    .'</div>';
  layout('Câmbio',$html); exit;
}

if ($page === 'salary') {
  $st = $pdo->prepare('SELECT * FROM salary_config WHERE user_id=?'); $st->execute([$u['id']]);
  $cfg = $st->fetch() ?: ['base_hour_rate'=>0,'meal_allowance_per_day'=>0,'overtime_multiplier'=>1.25];

  $month = $_GET['m'] ?? date('Y-m');
  $wl = $pdo->prepare("SELECT SUM(hours_worked) h, SUM(overtime_hours) ot, COUNT(*) d FROM work_logs WHERE user_id=? AND DATE_FORMAT(work_date,'%Y-%m')=?");
  $wl->execute([$u['id'],$month]);
  $tot = $wl->fetch() ?: ['h'=>0,'ot'=>0,'d'=>0];
  $gross = ($tot['h']*$cfg['base_hour_rate']) + ($tot['ot']*$cfg['base_hour_rate']*$cfg['overtime_multiplier']);
  $meals = ((int)$tot['d']) * $cfg['meal_allowance_per_day'];
  $estimate = $gross + $meals;

  $cfgForm = '<form method="post" action="?page=salarycfg_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">'
    .'<label>€ por hora<br><input class="input" type="number" step="0.01" name="base_hour_rate" value="'.h((string)$cfg['base_hour_rate']).'"></label>'
    .'<label>Alimentação/dia (€)<br><input class="input" type="number" step="0.01" name="meal_allowance_per_day" value="'.h((string)$cfg['meal_allowance_per_day']).'"></label>'
    .'<label>Multiplicador HE<br><input class="input" type="number" step="0.01" name="overtime_multiplier" value="'.h((string)$cfg['overtime_multiplier']).'"></label>'
    .'</div><br><button class="button primary">Guardar</button></form>';

  $logForm = '<form method="post" action="?page=worklog_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">'
    .'<label>Data<br><input class="input" type="date" name="work_date" value="'.date('Y-m-d').'"></label>'
    .'<label>Horas normais<br><input class="input" type="number" step="0.01" name="hours_worked" value="8"></label>'
    .'<label>Horas extra<br><input class="input" type="number" step="0.01" name="overtime_hours" value="0"></label>'
    .'</div><br><label>Notas<br><input class="input" name="notes"></label><br>'
    .'<button class="button success">Salvar registo</button></form>';

  $kpis = '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">'
    .'<div class="card"><div class="muted">Horas</div><div class="kpi">'.number_format((float)$tot['h'],2,',','.').'</div></div>'
    .'<div class="card"><div class="muted">Extras</div><div class="kpi">'.number_format((float)$tot['ot'],2,',','.').'</div></div>'
    .'<div class="card"><div class="muted">Estimativa (€)</div><div class="kpi">'.number_format((float)$estimate,2,',','.').'</div></div>'
    .'</div>';

  $html = nav('salary')
    .'<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">'
    .'<div class="card"><h3>Configuração</h3>'.$cfgForm.'</div>'
    .'<div class="card"><h3>Registo diário</h3>'.$logForm.'</div>'
    .'</div><br><div class="card"><h3>Mês '.$month.' • Simulação de ordenado</h3>'.$kpis.'<p class="muted">(Estimativa simples: sem descontos de IRS/Seg. Social.)</p></div>';
  layout('Ordenado (Portugal)',$html); exit;
}

header('Location:?page=dashboard');
