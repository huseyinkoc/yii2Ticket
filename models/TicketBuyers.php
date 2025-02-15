<?php

namespace app\modules\ticket_sales\models;

use Yii;
use yii\mongodb\ActiveRecord;

class TicketBuyers extends ActiveRecord
{
    public static function collectionName()
    {
        return 'ticket_buyers'; // MongoDB koleksiyon adı
    }

    public function attributes()
    {
        return ['_id', 'name', 'surname', 'id_number', 'email', 'phone', 'i_number', 'i_name', 'verified', 'created_at'];
    }

    public function rules()
    {
        return [
            [['phone'], 'required', 'on' => 'phone'],
            [['email'], 'required', 'on' => 'email'],
            [['name', 'surname' , 'i_number', 'i_name'], 'string', 'max' => 255],
            [['id_number'], 'string', 'max' => 20],
            [['email'], 'email'],
            [['email', 'phone'], 'unique'],
            [['verified'], 'boolean'],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'name' => 'Ad',
            'surname' => 'Soyad',
            'id_number' => 'Kimlik / Pasaport Numarası',
            'email' => 'E-posta',
            'phone' => 'Telefon Numarası',
            'i_number' => 'Kurum Numarası',
            'i_name' => 'Kurum Adı',
            'verified' => 'Doğrulandı mı?',
            'created_at' => 'Oluşturulma Tarihi',
        ];
    }
    
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {                               
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
            }
            return true;
        } else {
            return false;
        }
    }

    public static function findByPhone($phone)
    {
        return self::find()->where(['phone' => $phone])->one();
    }
    
    public static function findByEmail($email)
    {
        return self::find()->where(['email' => $email])->one();
    }

    public function getTickets()
    {
        return $this->hasMany(Ticket::class, ['buyer_id' => '_id']);
    }
}
