<?php $page_css = 'dashboard.css'; ?>
<div class="grid cols-3">
    <div class="card">
        <div class="muted">Receitas (EUR)</div>
        <div class="kpi"><?= number_format($sum['income'], 2, ',', '.') ?></div>
    </div>
    <div class="card">
        <div class="muted">Despesas (EUR)</div>
        <div class="kpi"><?= number_format($sum['expense'], 2, ',', '.') ?></div>
    </div>
    <div class="card">
        <div class="muted">Balanço (EUR)</div>
        <div class="kpi"><?= number_format($balance, 2, ',', '.') ?></div>
    </div>
</div>
<br>
<div class="card">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="button primary" data-open="modal-entry">+ Novo lançamento</button>
        <button class="button" data-open="modal-rate">Atualizar câmbio</button>
        <button class="button" data-open="modal-salary">Simular ordenado (PT)</button>
    </div>
    <h3 style="margin-top:16px">Últimos lançamentos</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>EUR</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['entry_date']) ?></td>
                    <td><span class="badge"><?= h($r['kind']) ?></span></td>
                    <td><?= h($r['category']) ?></td>
                    <td><?= h($r['description'] ?? '') ?></td>
                    <td><?= number_format((float) $r['amount'], 2, ',', '.') ?>     <?= h($r['currency_code']) ?></td>
                    <td><?= number_format((float) $r['amount_eur'], 2, ',', '.') ?> EUR</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<?php include __DIR__ . '/../partials/modal_entry.php'; ?>
<?php include __DIR__ . '/../partials/modal_rate.php'; ?>
<?php include __DIR__ . '/../partials/modal_salary_sim.php'; ?>