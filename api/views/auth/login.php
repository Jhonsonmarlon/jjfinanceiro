<?php $page_css = 'auth.css'; ?>
<div class="card" style="max-width:520px;margin:32px auto">
    <h2>Entrar</h2>
    <form method="post" action="?r=auth/login">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <label>Email<br><input class="input" type="email" name="email" required></label><br><br>
        <label>Senha<br><input class="input" type="password" name="pass" required></label><br><br>
        <button class="button success" type="submit">Entrar</button>
        <a class="button" href="?r=auth/register">Criar conta</a>
    </form>
</div>