<?php
function rates_save_post(PDO $pdo): void {
if (!csrf_ok()) { flash('CSRF inválido','danger'); header('Location:?r=dashboard/home'); return; }
$code=strtoupper(trim($_POST['currency_code']??''));
$rate=(float)($_POST['rate_eur']??0);
$date=$_POST['rate_date']??date('Y-m-d');
if(!$code||$rate<=0){ flash('Informe moeda e taxa válida.','danger'); header('Location:?r=dashboard/home'); return; }
$st=$pdo->prepare('INSERT INTO currency_rates(currency_code,rate_eur,rate_date) VALUES(:c,:r,:d) ON CONFLICT (currency_code,rate_date) DO UPDATE SET rate_eur=EXCLUDED.rate_eur');
$st->execute([':c'=>$code,':r'=>$rate,':d'=>$date]);
flash('Taxa de câmbio atualizada.'); header('Location:?r=dashboard/home');
}
?>