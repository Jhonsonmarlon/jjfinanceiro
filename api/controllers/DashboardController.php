<?php
function dashboard_home_get(PDO $pdo): void {
// KPIs mensais
$uid = (int)($_SESSION['uid'] ?? 0);
$month = $_GET['m'] ?? date('Y-m');
$st=$pdo->prepare("SELECT kind, SUM(amount_eur) v FROM entries WHERE user_id=:u AND to_char(entry_date,'YYYY-MM')=:m GROUP BY kind");
$st->execute([':u'=>$uid,':m'=>$month]);
$sum=['income'=>0,'expense'=>0,'transfer'=>0]; foreach($st as $r){ $sum[$r['kind']] = (float)$r['v']; }
$balance = $sum['income'] - $sum['expense'];


// últimos
$recent=$pdo->prepare('SELECT entry_date,kind,category,description,amount,currency_code,amount_eur FROM entries WHERE user_id=:u ORDER BY entry_date DESC, id DESC LIMIT 10');
$recent->execute([':u'=>$uid]);
$rows=$recent->fetchAll();


render('dashboard/home',[ 'month'=>$month, 'sum'=>$sum, 'balance'=>$balance, 'rows'=>$rows ]);
}
?>