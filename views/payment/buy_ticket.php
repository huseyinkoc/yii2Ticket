<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Bilet Satın Al';
$csrfToken = Yii::$app->request->csrfToken;
?>

<div id="app" class="container mt-5">
    <h2 class="text-center text-primary mb-4">🎟️ Bilet Satın Al</h2>

    <!-- CSRF Token -->
    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
    

    <!-- Kart ve Güzergah Kartları -->
    <div class="row justify-content-center">
        <div class="col-md-6">            
            <div class="card shadow p-4">
                <h5 class="card-title text-center">🚍 Güzergah Seçimi</h5>                
                <div class="form-group">
                    <label for="route" class="fw-bold mb-2">Güzergah:</label>
                    <select v-model="selectedRoute" @change="fetchPrice" class="form-select">
                        <option disabled value="">Güzergah seçiniz</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?= $route['saatli_ulasim_listesi_adi'] ?>"><?= $route['saatli_ulasim_listesi_adi'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div v-if="price" class="alert alert-info mt-3 text-center">
                    <strong>Ücret:</strong> {{ price }} ₺
                </div>
                
                <!-- Uyarı Mesajları -->
                <div v-if="message" :class="{'alert alert-success': messageType === 'success', 'alert alert-danger': messageType === 'error'}" class="alert text-center mt-3">
                    {{ message }}
                </div>

                <div v-if="price" class="mt-4">
                    <h5 class="text-center">💳 Ödeme Bilgileri</h5>
                    <input type="text" v-model="cardDetails.cardHolder" placeholder="Kart Sahibi Adı" class="form-control mb-2 rounded-pill">
                     <!-- Kredi Kartı Numarası -->
                    <input type="text" v-model="cardDetails.cardNumber" placeholder="Kart Numarası (XXXX XXXX XXXX XXXX)" 
                           class="form-control mb-2 rounded-pill" maxlength="19" @input="formatCardNumber">
                    <div class="d-flex justify-content-between">
                        <!-- Tarih (MM/YY Formatı) -->
                        <input type="text" v-model="cardDetails.expiryDate" placeholder="MM/YY" 
                               class="form-control me-2 rounded-pill" maxlength="5" @input="formatExpiryDate">
                        <input type="text" v-model="cardDetails.cvv" placeholder="CVV" class="form-control rounded-pill">
                    </div>
                        
                    <!-- Sözleşme Onay Kutusu -->
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" v-model="agreementAccepted" id="agreementCheck">
                        <label class="form-check-label" for="agreementCheck">
                            <strong>🔒</strong> <a href="#" target="_blank">Ön Bilgilendirme ve Hizmet Sözleşmesi'ni</a> okudum ve kabul ediyorum.
                        </label>
                    </div>

                    <button @click="initiatePayment" class="btn btn-success w-100 mt-3 rounded-pill" :disabled="!isPaymentEnabled">💰 Ödeme Yap</button>
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
            selectedRoute: '',
            price: '',
            message: '',
            messageType: '',
            agreementAccepted: false, // Sözleşme Onayı
            cardDetails: {
                cardHolder: '',
                cardNumber: '',
                expiryDate: '',
                cvv: ''
            },
            csrfToken: '<?= Html::encode($csrfToken) ?>'
        },
        computed: {
            isPaymentEnabled() {
                return this.agreementAccepted && this.cardDetails.cardHolder && this.cardDetails.cardNumber && this.cardDetails.expiryDate && this.cardDetails.cvv;
            }
        },
        methods: {
            fetchPrice() {
                this.price = ''; // Fiyatı sıfırla
                this.message = ''; // Önceki mesajları temizle

                axios.post('<?= Url::to(['/ticket_sales/payment/get-price']) ?>', {
                    route_name: this.selectedRoute
                }, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.price = response.data.price;
                        this.message = '';
                    } else {
                        this.message = response.data.message;
                        this.messageType = 'error';
                    }
                }).catch(() => {
                    this.message = 'Fiyat bilgisi alınamadı.';
                    this.messageType = 'error';
                });
            },
            initiatePayment() {
                
                if (!this.isPaymentEnabled) return;
        
                axios.post('<?= Url::to(['/ticket_sales/payment/initiate-payment']) ?>', {
                    route_name: this.selectedRoute,
                    card_holder: this.cardDetails.cardHolder,
                    card_number: this.cardDetails.cardNumber,
                    expiry_date: this.cardDetails.expiryDate,
                    cvv: this.cardDetails.cvv,
                    price: this.price
                }, {
                    headers: { 'X-CSRF-Token': this.csrfToken }
                }).then(response => {
                    if (response.data.status === 'success') {
                        this.showRedirectModal(); // Modalı göster

                        const div = document.createElement('div');
                        div.innerHTML = response.data.html;
                        document.body.appendChild(div);

                        const form = div.querySelector('form');
                        if (form) {
                            form.submit();
                        }
                    } else {
                        this.message = response.data.message;
                        this.messageType = 'error';
                    }
                }).catch(() => {
                    this.message = 'Ödeme sırasında bir hata oluştu.';
                    this.messageType = 'error';
                });
            },
            // Kredi Kartı Formatlama
            formatCardNumber(event) {
                let value = event.target.value.replace(/\D/g, '');
                value = value.match(/.{1,4}/g)?.join(' ') || value;
                this.cardDetails.cardNumber = value;
            },

            // Son Kullanma Tarihi Formatlama (MM/YY)
            formatExpiryDate(event) {
                let value = event.target.value.replace(/\D/g, '');
                if (value.length > 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                this.cardDetails.expiryDate = value;
            },
            showRedirectModal() {
                const modalHtml = `
                    <div class="modal fade" id="redirectModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-center p-4">
                                <h5>Ödeme İşlemi Devam Ediyor</h5>
                                <p>Lütfen bekleyin, bankaya yönlendiriliyorsunuz...</p>
                                <div class="spinner-border text-primary mt-2" role="status">
                                    <span class="visually-hidden">Yükleniyor...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const redirectModal = new bootstrap.Modal(document.getElementById('redirectModal'));
                redirectModal.show();
            }

        }
    });
</script>
