<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Müşteri Bilgileri';
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="container mt-5" id="app">
    <h2 class="text-center">Müşteri Bilgileri</h2>
    <p class="text-muted">E-fatura için bilgilerinizi doğru giriniz.</p>

    <div class="card shadow p-4">
        <form @submit.prevent="updateCustomer">
            <input type="hidden" v-model="csrfToken">

            <label class="form-label">Ad</label>
            <input type="text" v-model="customer.name" class="form-control" required>

            <label class="form-label mt-3">Soyad</label>
            <input type="text" v-model="customer.surname" class="form-control" required>

            <label class="form-label mt-3">Kimlik / Pasaport Numarası</label>
            <input type="text" v-model="customer.id_number" class="form-control" required>

            <label class="form-label mt-3">E-Posta</label>
            <input type="email" v-model="customer.email" class="form-control" :readonly="readonlyEmail" required>

            <label class="form-label mt-3">Telefon</label>
            <input type="text" v-model="customer.phone" class="form-control" :readonly="readonlyPhone" required>

            <button type="submit" class="btn btn-primary mt-3">Kaydet</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    new Vue({
        el: "#app",
        data() {
            return {
                customer: {
                    name: "",
                    surname: "",
                    id_number: "",
                    email: "",
                    phone: ""
                },
                readonlyEmail: false,
                readonlyPhone: false,
                csrfToken: "<?= Html::encode($csrfToken) ?>"
            };
        },
        mounted() {
            this.loadCustomer();
        },
        methods: {
            loadCustomer() {
                axios.get('<?= Url::to(['/ticket_sales/customer/edit-personal-info']) ?>')
                    .then(response => {
                        if (response.data.status === 'success' && response.data.customer) {
                            // Backend'den gelen müşteri verisini Vue.js nesnesine aktar
                            this.customer = Object.assign({}, response.data.customer);

                            // E-posta veya telefon doluysa readonly olarak ayarla
                            this.readonlyEmail = this.customer.readonlyEmail;
                            this.readonlyPhone = this.customer.readonlyPhone;

                            // Vue.js verisini manuel olarak güncelle
                            this.$forceUpdate();
                        } else {
                            console.error("Beklenmeyen yanıt:", response.data);
                        }
                    })
                    .catch(error => {
                        console.error("Müşteri bilgileri yüklenirken hata oluştu:", error);
                    });
            },
            updateCustomer() {
                axios.post('<?= Url::to(['/ticket_sales/customer/edit-personal-info']) ?>', this.customer, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        window.location.href = '<?= Url::to(['/ticket_sales/customer/index']) ?>';
                    } else {
                        alert(response.data.message);
                    }
                }).catch(error => {
                    console.error("Güncelleme sırasında hata oluştu:", error);
                });
            }
        }
    });
</script>

