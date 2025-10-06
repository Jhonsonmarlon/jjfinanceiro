<?php $page_css = 'auth.css'; ?>
<div class="card" style="max-width:620px;margin:32px auto">
    <h2>Criar conta</h2>
    <form method="post" action="?r=auth/register">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="grid cols-2">
            <label>Nome<br><input class="input" type="text" name="name" required></label>
            <label>Email<br><input class="input" type="email" name="email" required></label>
        </div><br>
        <label>Senha (mín. 6)<br><input class="input" type="password" name="pass" required></label><br>
        <button class="button primary" type="submit">Criar</button>
        <a class="button" href="?r=auth/login">Já tenho conta</a>
    </form>
</div>