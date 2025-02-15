<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>


    <?= Html::beginForm($bankUrl, 'post', [
        'id' => 'creditCardForm',
    ]) ?>



        <?= Html::hiddenInput('posnetData', $data1) ?>
        <?= Html::hiddenInput('posnetData2', $data2) ?>
        <?= Html::hiddenInput('mid', $spBilgileri->bilgi_1) ?>
        <?= Html::hiddenInput('posnetID', $spBilgileri->bilgi_3) ?>
        <?= Html::hiddenInput('digest', $sign) ?>
        <?= Html::hiddenInput('vftCode', '') ?>
        <?= Html::hiddenInput('merchantReturnURL', Url::toRoute(['payment/payment-response'], true)) ?>
        <?= Html::hiddenInput('lang', 'tr') ?>

     

    <?= Html::endForm() ?>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('paymentForm').submit();
    });
</script>
