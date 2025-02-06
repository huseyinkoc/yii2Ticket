<?php

use yii\helpers\Url;
$this->title = 'Satın Alınan Biletler';
?>

<div class="container mt-5" id="app">
    <h2 class="text-center">Satın Alınan Biletler</h2>

    <div v-if="tickets.length > 0" class="row">
        <div v-for="ticket in tickets" :key="ticket._id" class="col-md-4">
            <div class="card shadow-sm p-3 mb-3">
                <h5 class="card-title text-center">{{ ticket.route }}</h5>
                <p class="card-text"><strong>Kalkış:</strong> {{ ticket.departure_time }}</p>
                <p class="card-text"><strong>Koltuk No:</strong> {{ ticket.seat_number }}</p>
                <p class="card-text"><strong>Fiyat:</strong> {{ ticket.price }} TL</p>
                <p class="card-text"><strong>Satın Alma Tarihi:</strong> {{ ticket.purchase_date }}</p>
                <p class="card-text"><strong>Durum:</strong> <span :class="getStatusClass(ticket.status)">{{ ticket.status }}</span></p>
                <img v-if="ticket.qr_code" :src="ticket.qr_code" alt="QR Kod" class="img-fluid">
            </div>
        </div>
    </div>

    <div v-else class="text-center">
        <p>Henüz bilet satın almadınız.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    new Vue({
        el: "#app",
        data() {
            return {
                tickets: []
            };
        },
        mounted() {
            this.loadTickets();
        },
        methods: {
            loadTickets() {
                axios.get('<?= Url::to(['/ticket_sales/customer/tickets']) ?>')
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.tickets = response.data.tickets;
                        }
                    })
                    .catch(error => {
                        console.error("Bilet bilgileri yüklenirken hata oluştu:", error);
                    });
            },
            getStatusClass(status) {
                switch (status) {
                    case 'active':
                        return 'text-success';
                    case 'used':
                        return 'text-warning';
                    case 'cancelled':
                        return 'text-danger';
                    default:
                        return 'text-secondary';
                }
            }
        }
    });
</script>
