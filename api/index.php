# ==============================
# /schema.sql
# ==============================
-- MySQL 8+ (utf8mb4)
CREATE DATABASE IF NOT EXISTS luso_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE luso_finance;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Taxas de câmbio guardadas por dia (1 unidade da moeda em EUR)
CREATE TABLE IF NOT EXISTS currency_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  currency_code CHAR(3) NOT NULL,
  rate_eur DECIMAL(18,8) NOT NULL,
  rate_date DATE NOT NULL,
  UNIQUE KEY (currency_code, rate_date)
) ENGINE=InnoDB;

-- Configurações salariais do usuário para simulação
CREATE TABLE IF NOT EXISTS salary_config (
  user_id INT UNSIGNED PRIMARY KEY,
  base_hour_rate DECIMAL(10,2) NOT NULL DEFAULT 0,        -- € por hora
  meal_allowance_per_day DECIMAL(10,2) NOT NULL DEFAULT 0, -- subsídio alimentação/dia
  overtime_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.25,  -- multiplicador hora extra
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Lançamentos de receitas/despesas/transferências multi‑moeda
CREATE TABLE IF NOT EXISTS entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind ENUM('income','expense','transfer') NOT NULL,
  category VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  amount DECIMAL(14,2) NOT NULL,           -- valor na moeda original
  currency_code CHAR(3) NOT NULL,          -- BRL, EUR, USD etc.
  fx_rate_eur DECIMAL(18,8) NOT NULL,      -- taxa usada (1 unidade da currency_code em EUR)
  amount_eur AS (amount * fx_rate_eur) STORED,
  account VARCHAR(60) NULL,                -- conta/cartão (PT/Brasil)
  entry_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id, entry_date)
) ENGINE=InnoDB;

-- Registo de horas diárias (Portugal)
CREATE TABLE IF NOT EXISTS work_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  hours_worked DECIMAL(5,2) NOT NULL DEFAULT 0,  -- horas normais
  overtime_hours DECIMAL(5,2) NOT NULL DEFAULT 0, -- horas extra
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY (user_id, work_date)
) ENGINE=InnoDB;

-- Registo de freelas (para controle de origem)
CREATE TABLE IF NOT EXISTS gigs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  client_name VARCHAR(120) NOT NULL,
  client_country VARCHAR(2) NOT NULL, -- 'PT' ou 'BR'
  expected_amount DECIMAL(14,2) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  due_date DATE NULL,
  status ENUM('negotiating','in_progress','delivered','paid','canceled') NOT NULL DEFAULT 'in_progress',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


# ==============================
# /public/styles.css
# ==============================
:root{--pt-green:#006400;--pt-red:#D40000;--br-green:#009B3A;--br-yellow:#FFDF00;--ink:#1e293b;--bg:#0b1020;--card:#121a33;--muted:#94a3b8}
*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:linear-gradient(135deg,var(--bg),#0f172a);color:#e2e8f0}
.header{display:flex;align-items:center;gap:12px;padding:18px 20px;background:linear-gradient(90deg,var(--pt-green),var(--br-green));box-shadow:0 6px 20px rgba(0,0,0,.25)}
.header .brand{font-weight:800;letter-spacing:.5px}
.flag{display:inline-block;width:20px;height:12px;border-radius:2px;box-shadow:0 0 0 1px #0003 inset}
.flag.br{background:linear-gradient(0deg,var(--br-green) 60%,var(--br-yellow) 60%);position:relative}
.flag.pt{background:linear-gradient(90deg,var(--pt-green) 50%,var(--pt-red) 50%)}
.container{max-width:1080px;margin:24px auto;padding:0 16px}
.card{background:var(--card);border:1px solid #243056;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,.2)}
.grid{display:grid;gap:16px}
.grid.cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.input,select,button{width:100%;padding:10px 12px;border-radius:12px;border:1px solid #2a3a63;background:#0e152b;color:#e5e7eb}
.button{cursor:pointer;font-weight:700}
.button.primary{background:linear-gradient(90deg,var(--br-yellow),#ffd84d);color:#0b1020;border:0}
.button.success{background:linear-gradient(90deg,#22c55e,#16a34a);border:0;color:white}
.button.danger{background:linear-gradient(90deg,#ef4444,#dc2626);border:0;color:white}
.badge{display:inline-block;padding:.25rem .55rem;border-radius:999px;font-size:.75rem;border:1px solid #334155;background:#0b1226;color:#cbd5e1}
.nav{display:flex;gap:10px;flex-wrap:wrap}
.nav a{color:#cbd5e1;text-decoration:none;padding:8px 12px;border-radius:10px;border:1px solid #243056}
.nav a.active{background:linear-gradient(90deg,var(--pt-green),var(--br-green));border:0}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border-bottom:1px solid #223055;padding:10px}
.table th{color:#93c5fd;text-transform:uppercase;font-size:.75rem;letter-spacing:.08em}
.kpi{font-size:1.4rem;font-weight:800}
.muted{color:var(--muted)}

#flash{margin: 0 auto;max-width: 1080px}
.flash{margin:16px;border-radius:12px;padding:12px;background:linear-gradient(90deg,#22c55e22,#16a34a22);border:1px solid #16a34a44}


# ==============================
# /public/index.php  (roteador simples + views)
# ==============================
<?php
// ============================================
// MVP Financeiro Luso‑Brasileiro
// Requisitos: PHP 8.1+, MySQL 8+, PDO habilitado
// Copie este arquivo para /public/index.php e styles.css para /public/styles.css
// Importe o /schema.sql no MySQL e ajuste as credenciais abaixo.
// ============================================
declare(strict_types=1);
session_start();

// ---- CONFIG DB ----
const DB_DSN  = getenv('DB_DSN');
const DB_USER = getenv('DB_USER');
const DB_PASS = getenv('DB_PASS');


$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ---- Helpers ----
function csrf_token(): string { if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); } return $_SESSION['csrf']; }
function csrf_ok(): bool { return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']); }
function require_login(): void { if (empty($_SESSION['uid'])) { header('Location: ?page=login'); exit; } }
function flash(string $msg, string $type='success'): void { $_SESSION['flash'] = [$msg,$type]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES,'UTF-8'); }

// ---- Auth ----
function user(): ?array { global $pdo; if (empty($_SESSION['uid'])) return null; $st=$pdo->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$_SESSION['uid']]); return $st->fetch(PDO::FETCH_ASSOC) ?: null; }

// Seed taxa padrão (caso não exista) – EUR=1, BRL ~ 0.18 EUR (exemplo)
$pdo->exec("INSERT IGNORE INTO currency_rates(currency_code,rate_eur,rate_date) VALUES
 ('EUR',1.00000000,CURDATE()),
 ('BRL',0.18000000,CURDATE())");

$page = $_GET['page'] ?? 'dashboard';

// ---- Rotas POST ----
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if ($page==='register' && csrf_ok()) {
    $name = trim($_POST['name']??'');
    $email= trim($_POST['email']??'');
    $pass = $_POST['pass']??'';
    if (!$name||!$email||strlen($pass)<6){ flash('Preencha nome, email e uma senha (6+).','danger'); header('Location:?page=register'); exit; }
    $hash=password_hash($pass,PASSWORD_DEFAULT);
    $st=$pdo->prepare('INSERT INTO users(name,email,pass_hash) VALUES(?,?,?)');
    try { $st->execute([$name,$email,$hash]); flash('Cadastro concluído. Faça login.'); header('Location:?page=login'); exit; } catch (PDOException $e){ flash('Email já cadastrado.','danger'); header('Location:?page=register'); exit; }
  }
  if ($page==='login' && csrf_ok()) {
    $email=trim($_POST['email']??''); $pass=$_POST['pass']??'';
    $st=$pdo->prepare('SELECT * FROM users WHERE email=?'); $st->execute([$email]); $u=$st->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($pass,$u['pass_hash'])){ $_SESSION['uid']=$u['id']; flash('Bem‑vindo, '.$u['name'].'!'); header('Location:?page=dashboard'); exit; }
    flash('Credenciais inválidas.','danger'); header('Location:?page=login'); exit;
  }
  if ($page==='logout') { session_destroy(); header('Location:?page=login'); exit; }

  if ($page==='entry_save' && csrf_ok()) { require_login();
    $uid=user()['id'];
    $kind=$_POST['kind']??'expense';
    $category=trim($_POST['category']??'Geral');
    $description=trim($_POST['description']??'');
    $amount=(float)($_POST['amount']??0);
    $currency=strtoupper(trim($_POST['currency']??'EUR'));
    $account=trim($_POST['account']??'');
    $date=$_POST['entry_date']??date('Y-m-d');
    // taxa do dia escolhido (se não houver, pega a mais recente)
    $st=$pdo->prepare('SELECT rate_eur FROM currency_rates WHERE currency_code=? AND rate_date=?');
    $st->execute([$currency,$date]);
    $rate=$st->fetchColumn();
    if($rate===false){
      $st=$pdo->prepare('SELECT rate_eur FROM currency_rates WHERE currency_code=? ORDER BY rate_date DESC LIMIT 1');
      $st->execute([$currency]);
      $rate=$st->fetchColumn() ?: 1.0;
    }
    $st=$pdo->prepare('INSERT INTO entries(user_id,kind,category,description,amount,currency_code,fx_rate_eur,account,entry_date) VALUES(?,?,?,?,?,?,?,?,?)');
    $st->execute([$uid,$kind,$category,$description,$amount,$currency,$rate,$account,$date]);
    flash('Lançamento registado.'); header('Location:?page=entries'); exit;
  }

  if ($page==='rate_save' && csrf_ok()) { require_login();
    $code=strtoupper(trim($_POST['currency_code']??''));
    $rate=(float)($_POST['rate_eur']??0);
    $date=$_POST['rate_date']??date('Y-m-d');
    if(!$code||$rate<=0){ flash('Informe moeda e taxa válida.','danger'); header('Location:?page=rates'); exit; }
    $st=$pdo->prepare('INSERT INTO currency_rates(currency_code,rate_eur,rate_date) VALUES(?,?,?) ON DUPLICATE KEY UPDATE rate_eur=VALUES(rate_eur)');
    $st->execute([$code,$rate,$date]);
    flash('Taxa de câmbio atualizada.'); header('Location:?page=rates'); exit;
  }

  if ($page==='worklog_save' && csrf_ok()) { require_login();
    $uid=user()['id'];
    $work_date=$_POST['work_date']??date('Y-m-d');
    $hours=(float)($_POST['hours_worked']??0);
    $ot=(float)($_POST['overtime_hours']??0);
    $notes=trim($_POST['notes']??'');
    $st=$pdo->prepare('INSERT INTO work_logs(user_id,work_date,hours_worked,overtime_hours,notes) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE hours_worked=VALUES(hours_worked), overtime_hours=VALUES(overtime_hours), notes=VALUES(notes)');
    $st->execute([$uid,$work_date,$hours,$ot,$notes]);
    flash('Registo de horas salvo.'); header('Location:?page=salary'); exit;
  }

  if ($page==='salarycfg_save' && csrf_ok()) { require_login();
    $uid=user()['id'];
    $rate=(float)($_POST['base_hour_rate']??0);
    $meal=(float)($_POST['meal_allowance_per_day']??0);
    $mult=(float)($_POST['overtime_multiplier']??1.25);
    $st=$pdo->prepare('INSERT INTO salary_config(user_id,base_hour_rate,meal_allowance_per_day,overtime_multiplier) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE base_hour_rate=VALUES(base_hour_rate), meal_allowance_per_day=VALUES(meal_allowance_per_day), overtime_multiplier=VALUES(overtime_multiplier)');
    $st->execute([$uid,$rate,$meal,$mult]);
    flash('Configuração salarial atualizada.'); header('Location:?page=salary'); exit;
  }
}

// ---- Views ----
function layout(string $title, string $contentHtml): void {
  $flash = get_flash();
  echo '<!doctype html><html lang="pt"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title><link rel="stylesheet" href="styles.css"></head><body>';
  echo '<header class="header"><span class="flag pt"></span><span class="flag br"></span><div class="brand">Finanças 🇵🇹 x 🇧🇷</div></header>';
  echo '<div id="flash">'; if($flash){ echo '<div class="flash">'.h($flash[0]).'</div>'; } echo '</div>';
  echo '<div class="container">'.$contentHtml.'</div></body></html>';
}

function nav(string $active): string {
  $items = [
    'dashboard'=>'Painel',
    'entries'=>'Lançamentos',
    'rates'=>'Câmbio',
    'salary'=>'Ordenado (PT)',
  ];
  $links = array_map(function($k) use ($active,$items){ $cls=$k===$active?'active':''; return '<a class="'.$cls.'" href="?page='.$k.'">'.$items[$k].'</a>'; }, array_keys($items));
  $auth = empty($_SESSION['uid']) ? '<a href="?page=login">Entrar</a>' : '<a href="?page=logout" onclick="return confirm(\'Sair?\')">Sair</a>';
  return '<div class="nav">'.implode('',$links).'<span style="flex:1"></span>'.$auth.'</div>';
}

if ($page==='login') {
  $html = '<div class="container card" style="max-width:520px">'
    .'<h2>Entrar</h2>'
    .'<form method="post"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<label>Email<br><input class="input" type="email" name="email" required></label><br><br>'
    .'<label>Senha<br><input class="input" type="password" name="pass" required></label><br><br>'
    .'<button class="button success" type="submit">Entrar</button> '
    .'<a class="button" href="?page=register">Criar conta</a>'
    .'</form></div>';
  layout('Login',$html); exit;
}

if ($page==='register') {
  $html = '<div class="container card" style="max-width:620px">'
    .'<h2>Criar conta</h2>'
    .'<form method="post"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div class="grid cols-2">'
    .'<label>Nome<br><input class="input" type="text" name="name" required></label>'
    .'<label>Email<br><input class="input" type="email" name="email" required></label>'
    .'</div><br>'
    .'<label>Senha (mín. 6)<br><input class="input" type="password" name="pass" required></label><br>'
    .'<button class="button primary" type="submit">Criar</button> '
    .'<a class="button" href="?page=login">Já tenho conta</a>'
    .'</form></div>';
  layout('Cadastro',$html); exit;
}

require_login();
$u = user();

if ($page==='dashboard') {
  // KPIs mensais (EUR convertido)
  $month = $_GET['m'] ?? date('Y-m');
  [$yr,$mo] = explode('-', $month);
  $st=$pdo->prepare("SELECT kind, SUM(amount_eur) v FROM entries WHERE user_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=? GROUP BY kind");
  $st->execute([$u['id'],$month]);
  $sum=['income'=>0,'expense'=>0,'transfer'=>0]; foreach($st as $r){ $sum[$r['kind']] = (float)$r['v']; }
  $balance = $sum['income'] - $sum['expense'];

  $kpis = '<div class="grid cols-3">'
    .'<div class="card"><div class="muted">Receitas (EUR)</div><div class="kpi">'.number_format($sum['income'],2,',','.').'</div></div>'
    .'<div class="card"><div class="muted">Despesas (EUR)</div><div class="kpi">'.number_format($sum['expense'],2,',','.').'</div></div>'
    .'<div class="card"><div class="muted">Balanço (EUR)</div><div class="kpi">'.number_format($balance,2,',','.').'</div></div>'
    .'</div>';

  $recent = $pdo->prepare("SELECT entry_date,kind,category,description,amount,currency_code,amount_eur FROM entries WHERE user_id=? ORDER BY entry_date DESC, id DESC LIMIT 10");
  $recent->execute([$u['id']]);
  $rows='';
  foreach($recent as $r){
    $rows.='<tr>'
      .'<td>'.h($r['entry_date']).'</td>'
      .'<td><span class="badge">'.h($r['kind']).'</span></td>'
      .'<td>'.h($r['category']).'</td>'
      .'<td>'.h($r['description']??'').'</td>'
      .'<td>'.number_format((float)$r['amount'],2,',','.').' '.h($r['currency_code']).'</td>'
      .'<td>'.number_format((float)$r['amount_eur'],2,',','.').' EUR</td>'
      .'</tr>';
  }

  $html = nav('dashboard')
    .'<br><div class="grid cols-3">'
    .'<div class="card"><h3>Resumo de '+h($month)+'</h3>'.$kpis.'</div>'
    .'<div class="card" style="grid-column: span 2"><h3>Últimos lançamentos</h3>'
    .'<table class="table"><thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Descrição</th><th>Valor</th><th>EUR</th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
    .'</div>';
  layout('Painel',$html); exit;
}

if ($page==='entries') {
  // Lista + formulário rápido
  $st=$pdo->prepare("SELECT * FROM entries WHERE user_id=? ORDER BY entry_date DESC, id DESC LIMIT 100");
  $st->execute([$u['id']]);
  $rows='';
  foreach($st as $r){
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
    .'<div class="grid cols-3">'
    .'<label>Tipo<br><select name="kind"><option value="expense">Despesa</option><option value="income">Receita</option><option value="transfer">Transferência</option></select></label>'
    .'<label>Categoria<br><input class="input" name="category" placeholder="Mercado, Renda, Gasolina..."></label>'
    .'<label>Conta<br><input class="input" name="account" placeholder="PT‑IBAN, Nubank, Itaú..."></label>'
    .'</div><br>'
    .'<div class="grid cols-3">'
    .'<label>Valor<br><input class="input" type="number" step="0.01" name="amount" required></label>'
    .'<label>Moeda<br><input class="input" name="currency" value="EUR" maxlength="3"></label>'
    .'<label>Data<br><input class="input" type="date" name="entry_date" value="'.date('Y-m-d').'"></label>'
    .'</div><br>'
    .'<label>Descrição<br><input class="input" name="description" placeholder="Detalhes"></label><br>'
    .'<button class="button success" type="submit">Adicionar</button>'
    .'</form>';

  $html = nav('entries')
    .'<div class="grid cols-2">'
    .'<div class="card"><h3>Novo lançamento</h3>'.$form.'</div>'
    .'<div class="card"><h3>Últimos 100</h3><table class="table"><thead><tr><th>Data</th><th>Tipo</th><th>Categoria</th><th>Conta</th><th>Valor</th><th>EUR</th><th>Descrição</th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
    .'</div>';
  layout('Lançamentos',$html); exit;
}

if ($page==='rates') {
  $st=$pdo->query("SELECT currency_code, rate_eur, rate_date FROM currency_rates ORDER BY rate_date DESC, currency_code");
  $rows=''; foreach($st as $r){ $rows.='<tr><td>'.h($r['currency_code']).'</td><td>'.number_format((float)$r['rate_eur'],6,',','.').'</td><td>'.h($r['rate_date']).'</td></tr>'; }

  $form = '<form method="post" action="?page=rate_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div class="grid cols-3">'
    .'<label>Moeda (ex.: BRL, EUR)<br><input class="input" name="currency_code" maxlength="3" required></label>'
    .'<label>Taxa para EUR<br><input class="input" type="number" step="0.00000001" name="rate_eur" required></label>'
    .'<label>Data<br><input class="input" type="date" name="rate_date" value="'.date('Y-m-d').'"></label>'
    .'</div><br><button class="button primary">Guardar/Atualizar</button></form>';

  $html = nav('rates')
    .'<div class="grid cols-2">'
    .'<div class="card"><h3>Atualizar taxa</h3>'.$form.'</div>'
    .'<div class="card"><h3>Histórico</h3><table class="table"><thead><tr><th>Moeda</th><th>Taxa→EUR</th><th>Data</th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
    .'</div>';
  layout('Câmbio',$html); exit;
}

if ($page==='salary') {
  // Carrega config
  $cfg = $pdo->prepare('SELECT * FROM salary_config WHERE user_id=?');
  $cfg->execute([$u['id']]);
  $cfg = $cfg->fetch(PDO::FETCH_ASSOC) ?: ['base_hour_rate'=>0,'meal_allowance_per_day'=>0,'overtime_multiplier'=>1.25];

  // Totais do mês corrente
  $month = $_GET['m'] ?? date('Y-m');
  $wl = $pdo->prepare("SELECT SUM(hours_worked) h, SUM(overtime_hours) ot, COUNT(*) d FROM work_logs WHERE user_id=? AND DATE_FORMAT(work_date,'%Y-%m')=?");
  $wl->execute([$u['id'],$month]);
  $tot=$wl->fetch(PDO::FETCH_ASSOC) ?: ['h'=>0,'ot'=>0,'d'=>0];
  $gross = ($tot['h']*$cfg['base_hour_rate']) + ($tot['ot']*$cfg['base_hour_rate']*$cfg['overtime_multiplier']);
  $meals = ((int)$tot['d']) * $cfg['meal_allowance_per_day'];
  $estimate = $gross + $meals; // Simples (sem descontos)

  // Form config
  $cfgForm = '<form method="post" action="?page=salarycfg_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div class="grid cols-3">'
    .'<label>€ por hora<br><input class="input" type="number" step="0.01" name="base_hour_rate" value="'.h((string)$cfg['base_hour_rate']).'"></label>'
    .'<label>Subsídio alimentação/dia (€)<br><input class="input" type="number" step="0.01" name="meal_allowance_per_day" value="'.h((string)$cfg['meal_allowance_per_day']).'"></label>'
    .'<label>Multiplicador HE<br><input class="input" type="number" step="0.01" name="overtime_multiplier" value="'.h((string)$cfg['overtime_multiplier']).'"></label>'
    .'</div><br><button class="button primary">Guardar</button></form>';

  // Form registo diário
  $logForm = '<form method="post" action="?page=worklog_save"><input type="hidden" name="csrf" value="'.csrf_token().'">'
    .'<div class="grid cols-3">'
    .'<label>Data<br><input class="input" type="date" name="work_date" value="'.date('Y-m-d').'"></label>'
    .'<label>Horas normais<br><input class="input" type="number" step="0.01" name="hours_worked" value="8"></label>'
    .'<label>Horas extra<br><input class="input" type="number" step="0.01" name="overtime_hours" value="0"></label>'
    .'</div><br><label>Notas<br><input class="input" name="notes"></label><br>'
    .'<button class="button success">Salvar registo</button></form>';

  $kpis = '<div class="grid cols-3">'
    .'<div class="card"><div class="muted">Horas</div><div class="kpi">'.number_format((float)$tot['h'],2,',','.').'</div></div>'
    .'<div class="card"><div class="muted">Extras</div><div class="kpi">'.number_format((float)$tot['ot'],2,',','.').'</div></div>'
    .'<div class="card"><div class="muted">Estimativa (€)</div><div class="kpi">'.number_format((float)$estimate,2,',','.').'</div></div>'
    .'</div>';

  $html = nav('salary')
    .'<div class="grid cols-2">'
    .'<div class="card"><h3>Configuração</h3>'.$cfgForm.'</div>'
    .'<div class="card"><h3>Registo diário</h3>'.$logForm.'</div>'
    .'</div><br>'
    .'<div class="card"><h3>Mês '.$month.' • Simulação de ordenado</h3>'.$kpis.'<p class="muted">(Estimativa simples: sem descontos legais/IRS/SS.)</p></div>';

  layout('Ordenado (Portugal)',$html); exit;
}

// fallback
header('Location:?page=dashboard');
