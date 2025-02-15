<?php
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Müşteri Bilgileri';
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="container mt-5" id="app">
    <h2 class="text-center text-primary mb-4">📝 Müşteri Bilgileri</h2>
    <p class="text-muted text-center">E-fatura için bilgilerinizi doğru giriniz.</p>

    <div class="card shadow-lg p-4 rounded-4">
        <!-- Dynamic Error Message -->
        <div v-if="message" :class="{'alert alert-success': messageType === 'success', 'alert alert-danger': messageType === 'error'}" class="alert text-center" role="alert">
            {{ message }}
        </div>

        <form @submit.prevent="updateCustomer">
            <input type="hidden" v-model="csrfToken">

            <label class="form-label">Ad</label>
            <input type="text" v-model="customer.name" class="form-control" required placeholder="Adınızı giriniz">

            <label class="form-label mt-3">Soyad</label>
            <input type="text" v-model="customer.surname" class="form-control" required placeholder="Soyadınızı giriniz">

            <label class="form-label mt-3">Kimlik / Pasaport Numarası</label>
            <input type="text" v-model="customer.id_number" class="form-control" required placeholder="Kimlik / Pasaport No">

            <div class="mb-3 position-relative">
                <label class="form-label">E-Posta</label>
                <input type="email" v-model="customer.email" class="form-control" :readonly="readonlyEmail" required placeholder="ornek@mail.com" @input="validateEmail">
                <small v-if="emailError" class="text-danger position-absolute" style="top: 100%; left: 0;">❗ Lütfen geçerli bir e-posta adresi girin.</small>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label">Telefon</label>
                <input type="text" v-model="customer.phone" class="form-control" placeholder="+90 (5XX) XXX-XXXX" :readonly="readonlyPhone" required @input="validatePhone">
                <small v-if="phoneError" class="text-danger position-absolute" style="top: 100%; left: 0;">❗ Lütfen geçerli bir telefon numarası girin.</small>
            </div>

            <button type="submit" class="btn btn-primary mt-4 w-100 rounded-pill">💾 Bilgileri Kaydet</button>
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
                emailError: false,
                phoneError: false,
                readonlyEmail: false,
                readonlyPhone: false,
                csrfToken: "<?= Html::encode($csrfToken) ?>",
                message: '',
                messageType: ''
            };
        },
        mounted() {
            this.loadCustomer();
        },
        methods: {
            validateEmail() {
                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                this.emailError = !emailPattern.test(this.customer.email);
            },
            validatePhone() {
                const phonePattern = /^\+90\s?\(?5\d{2}\)?\s?\d{3}\s?\d{4}$/; 
                this.phoneError = !phonePattern.test(this.customer.phone);
            },
            loadCustomer() {
                axios.get('<?= Url::to(['/ticket_sales/customer/edit-personal-info']) ?>')
                    .then(response => {
                        if (response.data.status === 'success' && response.data.customer) {
                            this.customer = Object.assign({}, response.data.customer);
                            this.readonlyEmail = this.customer.readonlyEmail;
                            this.readonlyPhone = this.customer.readonlyPhone;
                            this.$forceUpdate();
                        } else {
                            this.message = 'Müşteri bilgileri yüklenemedi.';
                            this.messageType = 'error';
                        }
                    })
                    .catch(() => {
                        this.message = 'Müşteri bilgileri yüklenirken hata oluştu.';
                        this.messageType = 'error';
                    });
            },
            updateCustomer() {
                if (this.emailError || this.phoneError) {
                    this.message = 'Lütfen tüm alanları doğru doldurunuz.';
                    this.messageType = 'error';
                    return;
                }

                axios.post('<?= Url::to(['/ticket_sales/customer/edit-personal-info']) ?>', this.customer, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.message = 'Bilgiler başarıyla güncellendi!';
                        this.messageType = 'success';
                        setTimeout(() => window.location.href = '<?= Url::to(['/ticket_sales/customer/index']) ?>', 1500);
                    } else {
                        this.message = response.data.message;
                        this.messageType = 'error';
                    }
                }).catch(() => {
                    this.message = 'Güncelleme sırasında hata oluştu.';
                    this.messageType = 'error';
                });
            }
        }
    });
</script>
