<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Aldığım Biletler';
?>

<div id="app" class="container mt-5">
    <h2 class="text-center mb-4 text-primary">🎟️ Aldığınız Biletler</h2>

    <div class="row">
        <div v-for="ticket in tickets" :key="ticket.id" class="col-md-4 mb-4">
            <div class="card shadow-lg p-4 text-center border-0 rounded-4">
                <h4 class="text-dark fw-bold mb-2">{{ ticket.route }}</h4>
                <p><strong>💸 Fiyat:</strong> {{ ticket.price }} ₺</p>
                <p><strong>📅 Tarih:</strong> {{ ticket.date }}</p>

                <!-- QR Kod Görseli (Küçük ve Ortalanmış) -->
                <div class="d-flex justify-content-center align-items-center my-3">
                    <img :src="ticket.qr_code" 
                         class="img-thumbnail qr-code shadow" 
                         @click="openQrModal(ticket.qr_code)" 
                         style="cursor: pointer; max-width: 120px; border-radius: 15px;" 
                         alt="QR Kod">
                </div>

                <div v-if="ticket.is_used" class="badge bg-secondary rounded-pill">Kullanıldı</div>
                <div v-else class="badge bg-success rounded-pill">Kullanılabilir</div>
            </div>
        </div>
    </div>

    <!-- QR Kod Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">📱 QR Kod Görüntüle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex justify-content-center align-items-center">
                    <!-- QR Kodun Büyük Hali (Ortalanmış) -->
                    <img :src="selectedQr" 
                         class="img-fluid border shadow-lg p-2 bg-white" 
                         alt="Büyük QR Kod" 
                         style="max-width: 300px; border-radius: 20px;">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vue & Axios -->
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    new Vue({
        el: '#app',
        data: {
            tickets: [],
            selectedQr: ''
        },
        mounted() {
            this.fetchTickets();
        },
        methods: {
            fetchTickets() {
                axios.get('<?= Url::to(['/ticket_sales/ticket/get-tickets']) ?>')
                    .then(response => {
                        this.tickets = response.data.tickets;
                    });
            },
            openQrModal(qrCode) {
                this.selectedQr = qrCode;
                new bootstrap.Modal(document.getElementById('qrModal')).show();
            }
        }
    });
</script>
