<?php

use yii\helpers\Url;
$this->title = 'Müşteri Paneli';
?>

<div class="container text-center mt-5">
    <h2>Hoşgeldiniz</h2>
    <div class="row mt-4">
        <div class="col-md-6">
            <a href="<?= Url::to(['/ticket_sales/customer/view-tickets']) ?>" class="card shadow p-4 text-decoration-none">
                <h4>Satın Alınan Biletler</h4>
            </a>
        </div>
        <div class="col-md-6">
            <a href="<?= Url::to(['/ticket_sales/customer/buy-ticket']) ?>" class="card shadow p-4 text-decoration-none">
                <h4>Bilet Satın Al</h4>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    new Vue({
        el: '#app',
        data: {
            hasPersonalInfo: <?= Yii::$app->session->has('customer_id') ? 'true' : 'false' ?>,
        },
        methods: {
            checkPersonalInfo() {
                if (!this.hasPersonalInfo) {
                    window.location.href = '<?= Url::to(['/ticket_sales/customer/personal-info']) ?>';
                } else {
                    window.location.href = '<?= Url::to(['/ticket_sales/customer/buy-ticket']) ?>';
                }
            }
        }
    });
</script>
