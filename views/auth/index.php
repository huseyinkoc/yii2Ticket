<?php

use yii\helpers\Url;
$this->title = 'Giriş Yap';
?>

<div class="container mt-5" id="app">
    <h2 class="text-center">Giriş Yap</h2>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <ul class="nav nav-tabs" id="loginTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="phone-tab" data-bs-toggle="tab" href="#phone-login">Telefon ile Giriş</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="email-tab" data-bs-toggle="tab" href="#email-login">E-posta ile Giriş</a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="phone-login" v-if="!isCodeSent">
                        <form @submit.prevent="sendSms">
                            <label for="phone" class="form-label">Telefon Numarası</label>
                            <input type="text" v-model="phone" class="form-control" placeholder="+90xxxxxxxxxx" required>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="kvkk-phone" v-model="kvkkAccepted">
                                <label class="form-check-label" for="kvkk-phone">
                                    KVKK Sözleşmesini okudum ve kabul ediyorum.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3" :disabled="!kvkkAccepted">Kod Gönder</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="email-login" v-if="!isCodeSent">
                        <form @submit.prevent="sendEmailCode">
                            <label for="email" class="form-label">E-Posta</label>
                            <input type="email" v-model="email" class="form-control" placeholder="example@email.com" required @input="validateEmail">
                            <p v-if="emailError" class="text-danger">Lütfen geçerli bir e-posta adresi girin.</p>
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="kvkk-email" v-model="kvkkAccepted">
                                <label class="form-check-label" for="kvkk-email">
                                    KVKK Sözleşmesini okudum ve kabul ediyorum.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3" :disabled="!kvkkAccepted || emailError">Kod Gönder</button>
                        </form>
                    </div>
                    <div class="mt-3" v-if="isCodeSent">
                        <h5>Kodu Girin</h5>
                        <p v-if="countdown > 0">Kod geçerlilik süresi: {{ countdown }} saniye</p>
                        <input type="text" v-model="code" class="form-control" placeholder="Gelen kodu girin" required>
                        <button @click="verifyCode" class="btn btn-success mt-3">Doğrula</button>
                    </div>
                    <!-- Dinamik Mesaj Alanı -->
                    <div v-if="message" :class="{'alert alert-success': messageType === 'success', 'alert alert-danger': messageType === 'error'}" class="alert mt-3" role="alert">
                        {{ message }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    new Vue({
        el: '#app',
        data: {
            phone: '',
            email: '',
            code: '',
            isCodeSent: false,
            kvkkAccepted: false,
            emailError: false,
            countdown: 0,
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        methods: {
            validateEmail() {
                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                this.emailError = !emailPattern.test(this.email);
            },
            sendSms() {
                this.phone = this.phone.replace(/\D/g, '');
                if (!this.phone.startsWith("90")) {
                    this.phone = "90" + this.phone;
                }
                this.phone = "+" + this.phone;

                axios.post('<?= Url::to(['/ticket_sales/auth/send-phone-code']) ?>', {
                    phone: this.phone
                }, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.isCodeSent = true;
                        this.message = 'Kod başarıyla gönderildi.';
                        this.messageType = 'success';
                        this.countdown = response.data.expiry;
                        setTimeout(this.startCountdown, 1000);
                    } else {
                        this.message = response.data.message;
                        this.messageType = 'error';
                    }
                });
            },
            sendEmailCode() {
                if (this.emailError) return;
                axios.post('<?= Url::to(['/ticket_sales/auth/send-email-code']) ?>', {
                    email: this.email
                }, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.isCodeSent = true;
                        this.message = 'Kod başarıyla gönderildi.';
                        this.messageType = 'success';
                        this.countdown = response.data.expiry;
                        setTimeout(this.startCountdown, 1000);
                    } else {
                        this.message = response.data.message;
                        this.messageType = 'error';
                    }
                });
            },
            verifyCode() {
                axios.post('<?= Url::to(['/ticket_sales/auth/verify-code']) ?>', {
                    code: this.code
                }, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        window.location.href = '<?= Url::to(['/ticket_sales/customer/index']) ?>';
                    } else {
                        this.message = response.data.message;
                        this.messageType = 'error';
                    }
                });
            },
            startCountdown() {
                let timer = setInterval(() => {
                    if (this.countdown > 0) {
                        this.countdown--;
                    } else {
                        clearInterval(timer);
                        window.location.href = '<?= Url::to(['/ticket_sales/auth/login']) ?>';
                    }
                }, 1000);
            }
        }
    });
</script>
