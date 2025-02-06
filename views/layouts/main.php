<?php

use yii\helpers\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\widgets\Breadcrumbs;
use yii\helpers\Url;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->registerCsrfMetaTags(); ?>
    <?php $this->head() ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .logout-btn {
            background: linear-gradient(135deg, #ff4b5c, #ff1a1a);
            border: none;
            color: white !important;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        .logout-btn:hover {
            background: linear-gradient(135deg, #e60023, #c4001a);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <?php $this->beginBody() ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?= Url::to(['/ticket_sales/auth/login']) ?>">
                <?=Html::img('@web/uploads/gurselLogo.png', ['alt' => 'Logo', 'style' => 'height:40px;'])?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= Url::to(['/ticket_sales/auth/login']) ?>">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= Url::to(['/ticket_sales/ticket/buy']) ?>">Bilet Satın Al</a>
                    </li>
                    <?php if (Yii::$app->session->has('customer_id')): ?>
                        <li class="nav-item">
                            <form id="logout-form" method="post" action="<?= Url::to(['/ticket_sales/auth/logout']) ?>">
                                <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
                                <button type="submit" class="nav-link logout-btn">Çıkış Yap</button>
                            </form>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs'] ?? []]) ?>
        <?= $content ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(); ?>
