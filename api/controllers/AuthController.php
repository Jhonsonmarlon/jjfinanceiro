<?php
function auth_login_get(): void { render('auth/login'); }
function auth_register_get(): void { render('auth/register'); }


function auth_register_post(PDO $pdo): void {
if (!csrf_ok()) { flash('CSRF inválido','danger'); header('Location:?r=auth/register'); return; }
$name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=(string)($_POST['pass']??'');
if(!$name||!$email||strlen($pass)<6){ flash('Preencha nome, email e senha (6+).','danger'); header('Location:?r=auth/register'); return; }
$hash=password_hash($pass,PASSWORD_DEFAULT);
try{ $st=$pdo->prepare('INSERT INTO users(name,email,pass_hash) VALUES(:n,:e,:p)'); $st->execute([':n'=>$name,':e'=>$email,':p'=>$hash]); flash('Cadastro concluído. Faça login.'); header('Location:?r=auth/login'); }
catch(PDOException $e){ flash('Email já cadastrado.','danger'); header('Location:?r=auth/register'); }
}


function auth_login_post(PDO $pdo): void {
if (!csrf_ok()) { flash('CSRF inválido','danger'); header('Location:?r=auth/login'); return; }
$email=trim($_POST['email']??''); $pass=(string)($_POST['pass']??'');
$st=$pdo->prepare('SELECT * FROM users WHERE email=:e'); $st->execute([':e'=>$email]); $u=$st->fetch();
if($u && password_verify($pass,$u['pass_hash'])){ $_SESSION['uid']=$u['id']; flash('Bem‑vindo, '.$u['name'].'!'); header('Location:?r=dashboard/home'); return; }
flash('Credenciais inválidas.','danger'); header('Location:?r=auth/login');
}


function auth_logout(): void { session_destroy(); header('Location:?r=auth/login'); }
?>