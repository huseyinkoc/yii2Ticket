<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $status === 'success' ? 'Ödeme Başarılı' : 'Ödeme Başarısız';
?>

<div class="container text-center mt-5">
    <?php if ($status === 'success'): ?>
        <div class="alert alert-success p-4 rounded shadow">
            <h2>✅ Ödeme Başarılı!</h2>
            <p><?= Html::encode($message) ?></p>
            <p><strong>Bilet Fiyatı:</strong> <?= Html::encode($ticket->price) ?> ₺</p>

            <div class="mt-4">
                <h4>🎟️ QR Kodunuz</h4>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= Html::encode($ticket->qr_code) ?>" alt="QR Kod" class="img-fluid mt-2">
            </div>

            <a href="<?= Url::to(['/ticket_sales/customer/index']) ?>" class="btn btn-primary mt-4">Ana Sayfaya Dön</a>
        </div>
    <?php else: ?>
        <div class="alert alert-danger p-4 rounded shadow">
            <h2>❌ Ödeme Başarısız!</h2>
            <p><?= Html::encode($message) ?></p>
            <a href="<?= Url::to(['/ticket_sales/customer/buy-ticket']) ?>" class="btn btn-danger mt-4">Tekrar Dene</a>
        </div>
    <?php endif; ?>
</div>
