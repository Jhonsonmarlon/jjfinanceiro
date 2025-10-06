<?php
function entries_save_post(PDO $pdo): void {
if (!csrf_ok()) { flash('CSRF inválido','danger'); header('Location:?r=dashboard/home'); return; }
$uid=(int)$_SESSION['uid'];
$kind=$_POST['kind']??'expense';
$category=trim($_POST['category']??'Geral');
$description=trim($_POST['description']??'');
$amount=(float)($_POST['amount']??0);
$currency=strtoupper(trim($_POST['currency']??'EUR'));
$account=trim($_POST['account']??'');
$date=$_POST['entry_date']??date('Y-m-d');


$st=$pdo->prepare('SELECT rate_eur FROM currency_rates WHERE currency_code=:c AND rate_date=:d');
$st->execute([':c'=>$currency,':d'=>$date]);
$rate=$st->fetchColumn();
if($rate===false){ $st=$pdo->prepare('SELECT rate_eur FROM currency_rates WHERE currency_code=:c ORDER BY rate_date DESC LIMIT 1'); $st->execute([':c'=>$currency]); $rate=$st->fetchColumn(); if($rate===false){$rate=1.0;} }
$amount_eur=round($amount*(float)$rate,2);


$st=$pdo->prepare('INSERT INTO entries(user_id,kind,category,description,amount,currency_code,fx_rate_eur,amount_eur,account,entry_date) VALUES(:u,:k,:cat,:desc,:amt,:cur,:fx,:eu,:acc,:dt)');
$st->execute([':u'=>$uid, ':k'=>$kind, ':cat'=>$category, ':desc'=>$description, ':amt'=>$amount, ':cur'=>$currency, ':fx'=>$rate, ':eu'=>$amount_eur, ':acc'=>$account, ':dt'=>$date]);
flash('Lançamento registado.'); header('Location:?r=dashboard/home');
}
?>