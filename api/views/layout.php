<?php /* Layout base (inclui nav + assets) */ ?>
<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>JJFinanceiro</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <?php if (!empty($page_css))
        echo '<link rel="stylesheet" href="/assets/css/' . h($page_css) . '">'; ?>
</head>

<body>
    <header class="header"><span class="flag pt"></span><span class="flag br"></span>
        <div class="brand">Finanças 🇵🇹 x 🇧🇷</div>
    </header>
    <div class="container">
        <?= nav_html($_GET['r'] ?? '') ?>
        <?php if (!empty($flash)): ?>
            <div class="flash"><?= h($flash[0]) ?></div><?php endif; ?>
        <?= $content ?>
    </div>
    <script src="/assets/js/app.js"></script>
</body>

</html>