<?php

namespace app\modules\ticket_sales\models;

use Yii;
use yii\mongodb\ActiveRecord;
use app\modules\yonetim\models\KartYuklemeHareketleri;
use app\modules\yonetim\models\FaturaliYuklemeler;
use app\modules\ticket_sales\models\TicketBuyers;;

class Tickets extends ActiveRecord {
    
    const EXP_TIME = 24;
    const ACTIVE = 'active';
    const USED = 'used';
    const CANCELED = 'cancelled';
    const EXPIRED = 'expired';
    
    public static function collectionName() {
        return 'tickets';
    }
    

    public function attributes() {
        return [
            '_id',
            'customer_id',
            'route',
            'order_id',
            'auth_code',
            'hostlog_key',
            'client_id',
            'response_txt',
            'price',
            'qr_code',
            'status',
            'expires_at',
            'created_at',
        ];
    }

    public function rules() {
        return [
            [['customer_id', 'route', 'price', 'status'], 'required'],
            [['customer_id'], 'string'],
            [['route'], 'string'],
            [['price'], 'number'],
            [['qr_code'], 'string'],
            [['status'], 'in', 'range' => ['active', 'used', 'cancelled', 'expired']],
        ];
    }

    public function attributeLabels() {
        return [
            '_id' => 'ID',
            'customer_id' => 'Müşteri ID',
            'route' => 'Güzergah',
            'price' => 'Ücret',
            'created_at' => 'Satın Alma Tarihi',
            'expires_at' => 'Son Kullama Tarihi',
            'qr_code' => 'QR Kod',
            'status' => 'Durum'
        ];
    }
    
    

    public function validateQrCode($qrCode, $route, $use = false) {
        
        //qr ile route karşılaştır 
        $model = Tickets::find()
                ->where(['route' => $route])
                ->andWhere(['!=', 'status', self::ACTIVE])
                ->andWhere(['qr_code' => $qrCode])
                ->one();
        
        if(!empty($model)){
            $changedStatus = null;
           if ($model && $model->expires_at > (time() * 1000)) {
                if($use){
                    $kartNum = (string) $model->customer_id;
                    $kartYHObj = new KartYuklemeHareketleri();
                    $balanceStatus = $kartYHObj->gurselEYukleme($kartNum, $model->price, $model->auth_code, $model->hostlog_key, $model->response_txt);
                    if(!$balanceStatus){
                        return null;
                    } else {
                        $ticketBuyerObj = TicketBuyers();
                        $email = $ticketBuyerObj->getEmailById($model->customer_id);
                        $faturaYObj = new FaturaliYuklemeler();
                        $faturaYObj->faturaBilgisiKaydet($kartNum, $faturaYObj::FATURA_ISLEMI_YUKLEME, $faturaYObj::FATURA_ISLEM_TURU_QR_KAGIT_BILET, null, $model->client_id, $model->order_id, $model->price, $email);
                        
                    }
                    $changedStatus = self::USED;
                }
                return (string) $model->customer_id;            
           } else {
                $changedStatus = self::EXPIRED;
                
           }
           
           if($changedStatus != null){
               $model->status = $changedStatus;
               $model->save();
           }
        }
        
        return null;
        
        
        
    }

}
