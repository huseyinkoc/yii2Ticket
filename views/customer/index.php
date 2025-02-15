<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Müşteri Paneli';
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="container mt-5" id="app">
    <h2 class="text-center">Müşteri Paneli</h2>

    <!-- Dinamik Mesaj Alanı -->
    <div v-if="message" :class="{'alert alert-success': messageType === 'success', 'alert alert-danger': messageType === 'error'}" class="alert mt-3" role="alert">
        {{ message }}
    </div>

    <div class="row">
        <div class="col-md-6">
            <div v-if="customer" class="card shadow-sm p-4 border-0" style="max-width: 400px; margin: auto;">
                <div class="card-body text-center">
                    <h5 class="card-title">{{ customer.name }} {{ customer.surname }}</h5>
                    <p class="mb-1"><strong>E-posta:</strong> {{ customer.email }}</p>
                    <p><strong>Telefon:</strong> {{ customer.phone }}</p>
                    <button class="btn btn-outline-primary btn-sm mt-2" @click="openEditModal">Bilgileri Düzenle</button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="container text-center mt-4">
                <div class="row">
                    <div class="col-md-12">
                        <a href="<?= Url::to(['/ticket_sales/ticket/index']) ?>" class="card shadow p-4 text-decoration-none">
                            <h4>Satın Alınan Biletler</h4>
                        </a>
                    </div>
                    <div class="col-md-12 mt-3">
                        <a href="<?= Url::to(['/ticket_sales/payment/buy-ticket']) ?>" class="card shadow p-4 text-decoration-none">
                            <h4>Bilet Satın Al</h4>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bilgi Düzenleme Modalı -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bilgileri Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                    <input type="text" v-model="customer.name" class="form-control mb-2" placeholder="Ad">
                    <input type="text" v-model="customer.surname" class="form-control mb-2" placeholder="Soyad">
                    <input type="email" v-model="customer.email" class="form-control mb-2" placeholder="E-posta">
                    <input type="text" v-model="customer.phone" class="form-control" placeholder="Telefon">
                    <div v-if="modalMessage" :class="{'alert alert-success': modalMessageType === 'success', 'alert alert-danger': modalMessageType === 'error'}" class="alert mt-3" role="alert">
                        {{ modalMessage }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeEditModal">Kapat</button>
                    <button type="button" class="btn btn-primary" @click="saveCustomerInfo">Kaydet</button>
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
            customer: {
                name: '<?=$customer->name?>',
                surname: '<?=$customer->surname?>',
                email: '<?=$customer->email?>',
                phone: '<?=$customer->phone?>'
            },
            message: '',
            messageType: '',
            modalMessage: '',
            modalMessageType: '',
            modalInstance: null
        },
        mounted() {
            this.fetchCustomerData();
        },
        methods: {
            fetchCustomerData() {
                axios.get('<?= Url::to(['/ticket_sales/customer/get-customer']) ?>')
                    .then(response => {
                        this.customer = response.data;
                    });
            },
            openEditModal() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('editModal'));
                this.modalInstance.show();
            },
            closeEditModal() {
                if (this.modalInstance) {
                    this.modalInstance.hide();
                }
            },
            saveCustomerInfo() {
                axios.post('<?= Url::to(['/ticket_sales/customer/edit-personal-info']) ?>', this.customer, {
                    headers: { 'X-CSRF-Token': '<?= Html::encode($csrfToken) ?>' }
                })
                .then(response => {
                    this.modalMessage = 'Bilgiler güncellendi.';
                    this.modalMessageType = 'success';
                    setTimeout(() => {
                        this.closeEditModal();
                        this.modalMessage = '';
                    }, 2000);
                })
                .catch(() => {
                    this.modalMessage = 'Güncelleme sırasında hata oluştu.';
                    this.modalMessageType = 'error';
                });
            }
        }
    });
</script>
