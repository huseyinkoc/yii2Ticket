<?php

use yii\helpers\Url;
$this->title = 'Bilet Satın Al';
?>

<div class="container mt-5">
    <h2 class="text-center">Bilet Satın Al</h2>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <form id="buy-ticket-form" @submit.prevent="buyTicket">
                    <label for="route" class="form-label">Güzergah Seçin</label>
                    <select v-model="route" class="form-control" required>
                        <option value="">Seçiniz</option>
                        <option v-for="route in routes" :value="route">{{ route }}</option>
                    </select>
                    
                    <label for="date" class="form-label mt-3">Tarih Seçin</label>
                    <input type="date" v-model="date" class="form-control" required>
                    
                    <button type="submit" class="btn btn-success mt-3">Satın Al</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    new Vue({
        el: '#buy-ticket-form',
        data: {
            route: '',
            date: '',
            routes: ['Ankara - İstanbul', 'İstanbul - İzmir', 'İzmir - Antalya']
        },
        methods: {
            buyTicket() {
                axios.post('<?= Url::to(['/ticket_sales/ticket/purchase']) ?>', {
                    route: this.route,
                    date: this.date
                }).then(response => {
                    if (response.data.status === 'success') {
                        window.location.href = '<?= Url::to(['/ticket_sales/customer/tickets']) ?>';
                    } else {
                        alert(response.data.message);
                    }
                });
            }
        }
    });
</script>
