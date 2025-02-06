<?php

namespace app\modules\ticket_sales\services;

use Yii;
use app\modules\ticket_sales\models\TicketBuyers;
use app\models\ConverterObject;

class AuthService
{
    public function sendPhoneCode($phone)
    {
        $user = TicketBuyers::findByPhone($phone);
        if (!$user) {
            $user = new TicketBuyers();
            $user->phone = $phone;
            $user->verified = false;
            $user->created_at = date('Y-m-d H:i:s');
            $user->save();
        }

        $ticketLoginCode = rand(100000, 999999);
        $expiryTime = time() + 300; // 5 dakika geçerlilik süresi
        Yii::$app->session->set('ticket_login_code', $ticketLoginCode);
        Yii::$app->session->set('phone', $phone);
        
        $cObj = new ConverterObject();
        $cObj->smsSend($phone, $ticketLoginCode);

        return ['status' => 'success', 'message' => 'SMS kodu gönderildi', 'expiry' => $expiryTime - time()];
    }
    
    public function sendEmailCode($email)
    {
        $user = TicketBuyers::findByEmail($email);
        if (!$user) {
            $user = new TicketBuyers();
            $user->email = $email;
            $user->verified = false;
            $user->created_at = date('Y-m-d H:i:s');
            $user->save();
        }

        $ticketLoginCode = rand(100000, 999999);
        $expiryTime = time() + 300; // 5 dakika geçerlilik süresi
        Yii::$app->session->set('ticket_login_code', $ticketLoginCode);
        Yii::$app->session->set('email', $email);
        
        $cObj = new ConverterObject();
        $cObj->sendEmail($email, $ticketLoginCode);

        return ['status' => 'success', 'message' => 'Email kodu gönderildi', 'expiry' => $expiryTime - time()];
    }

    public function verifySmsCode($code)
    {
        $sessionCode = Yii::$app->session->get('ticket_login_code');
        $phone = Yii::$app->session->get('phone');
        $email = Yii::$app->session->get('email');

        if ($code != $sessionCode) {
            return ['status' => 'error', 'message' => 'Geçersiz doğrulama kodu'];
        }

        if(!empty($phone)){
            $user = TicketBuyers::findByPhone($phone);
        } else {
            $user = TicketBuyers::findByEmail($email);
        }
        if ($user) {
            $user->verified = true;
            $user->save();
            Yii::$app->session->set('customer_id', (string) $user->_id);
        }

        return ['status' => 'success', 'message' => 'Kod doğrulandı, giriş yapıldı'];
    }

    public function logout()
    {
        Yii::$app->session->remove('customer_id');
        Yii::$app->session->remove('phone');
        Yii::$app->session->remove('email');
        Yii::$app->session->remove('ticket_login_code');
        return ['status' => 'success', 'message' => 'Çıkış yapıldı'];
    }
}
