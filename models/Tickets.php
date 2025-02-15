<?php

namespace app\modules\ticket_sales\models;

use Yii;
use yii\mongodb\ActiveRecord;

class Tickets extends ActiveRecord {

    public static function collectionName() {
        return 'tickets';
    }

    public function attributes() {
        return [
            '_id',
            'customer_id',
            'route',
            'departure_time',
            'seat_number',
            'price',
            'purchase_date',
            'qr_code',
            'status'
        ];
    }

    public function rules() {
        return [
            [['customer_id', 'route', 'departure_time', 'seat_number', 'price', 'purchase_date', 'status'], 'required'],
            [['customer_id'], 'string'],
            [['route'], 'string'],
            [['departure_time', 'purchase_date'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['seat_number'], 'integer'],
            [['price'], 'number'],
            [['qr_code'], 'string'],
            [['status'], 'in', 'range' => ['active', 'used', 'cancelled']],
        ];
    }

    public function attributeLabels() {
        return [
            '_id' => 'ID',
            'customer_id' => 'Müşteri ID',
            'route' => 'Güzergah',
            'departure_time' => 'Kalkış Zamanı',
            'seat_number' => 'Koltuk Numarası',
            'price' => 'Ücret',
            'purchase_date' => 'Satın Alma Tarihi',
            'qr_code' => 'QR Kod',
            'status' => 'Durum'
        ];
    }

    public function validateQrCode($qrCode) {
        if (strpos($qrCode, 'TKT-') === 0) {
            $qrHash = substr($qrCode, 4); // 'TKT-' çıkarılır
            // $qrHash ile doğrulama işlemleri yapılır
            return true; // Geçerli QR kodu
        }
        return false; // Geçersiz QR kodu
    }

}
