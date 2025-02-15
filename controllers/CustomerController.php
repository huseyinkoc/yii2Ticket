<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use app\modules\ticket_sales\models\TicketBuyers;
use yii\web\Response;
use app\modules\yonetim\models\Kisiler;
use app\modules\yonetim\models\Kartlar;
use app\modules\yonetim\models\KisiKartlari;

class CustomerController extends \app\modules\ticket_sales\components\BaseController {

    public function actionIndex() {
        $customerId = Yii::$app->session->get('customer_id');

        if (!$customerId) {
            return $this->redirect(['/ticket_sales/auth/index']);
        }

        try {
            $customer = TicketBuyers::findOne(['_id' => $customerId]);
        } catch (\Exception $e) {
            $customer = null;
        }


        if (!$customer || !$customer->name || !$customer->surname || !$customer->id_number || !$customer->email || !$customer->phone) {
            return $this->redirect(['/ticket_sales/customer/personal-info']);
        }

        return $this->render('index', ['customer' => $customer]);
    }

    // CustomerController.php içinde ekleyeceğin method

    public function actionGetCustomer() {
        $session = Yii::$app->session;
        $customerId = $session->get('customer_id');

        if (!$customerId) {
            return $this->asJson([
                        'status' => 'error',
                        'message' => 'Müşteri kimliği bulunamadı.'
            ]);
        }

        $customer = TicketBuyers::findOne(['_id' => new \MongoDB\BSON\ObjectID($customerId)]);

        if ($customer) {
            return $this->asJson([
                        'status' => 'success',
                        'name' => $customer->name,
                        'surname' => $customer->surname,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'i_number' => $customer->i_number,
            ]);
        } else {
            return $this->asJson([
                        'status' => 'error',
                        'message' => 'Müşteri bilgileri bulunamadı.'
            ]);
        }
    }

    public function actionPersonalInfo() {
        $customerId = Yii::$app->session->get('customer_id');
        if (!$customerId) {
            return $this->redirect(['/ticket_sales/auth/index']);
        }

        try {
            $customer = TicketBuyers::findOne(['_id' => $customerId]);
        } catch (\Exception $e) {
            $customer = null;
        }

        if (!$customer) {
            $customer = new TicketBuyers();
            $customer->_id = $customerId;
        }

        $readonlyEmail = !empty($customer->email);
        $readonlyPhone = !empty($customer->phone);

        return $this->render('personal_info', [
                    'customer' => $customer,
        ]);
    }

    public function actionEditPersonalInfo() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $customerId = Yii::$app->session->get('customer_id');
        if (!$customerId) {
            return ['status' => 'error', 'message' => 'Giriş yapmalısınız.'];
        }

        $customer = TicketBuyers::findOne(['_id' => $customerId]);
        if (!$customer) {
            $customer = new TicketBuyers();
            $customer->_id = $customerId;
        }


        if (Yii::$app->request->isGet) {
            return [
                'status' => 'success',
                'customer' => [
                    'name' => $customer->name ?? '',
                    'surname' => $customer->surname ?? '',
                    'id_number' => $customer->id_number ?? '',
                    'email' => $customer->email ?? '',
                    'phone' => $customer->phone ?? '',
                    'readonlyEmail' => !empty($customer->email),
                    'readonlyPhone' => !empty($customer->phone),
                ]
            ];
        }

        if (Yii::$app->request->isPost) {
            $bodyParams = json_decode(Yii::$app->request->getRawBody(), true);

            if (!$bodyParams) {
                return ['status' => 'error', 'message' => 'Geçersiz veri formatı.'];
            }

            $customer->name = $bodyParams['name'] ?? $customer->name;
            $customer->surname = $bodyParams['surname'] ?? $customer->surname;
            $customer->id_number = $bodyParams['id_number'] ?? $customer->id_number;
            $customer->i_number = Yii::$app->session->get('i_number');
            $customer->i_name = Yii::$app->session->get('i_name');

            if (empty($customer->email)) {
                $customer->email = $bodyParams['email'] ?? $customer->email;
            }

            if (empty($customer->phone)) {
                $customer->phone = $bodyParams['phone'] ?? $customer->phone;
            }

            if ($customer->validate() && $customer->save()) {

                // burada kişi kaydı yok ise oluştur. yok ise kart oluştur.
                $kisilerObj = new Kisiler();
                $kisiModel = $kisilerObj->modelKisiBilgisi($customer->email);
                if (empty($kisiModel)) {
                    $kisiModel = new Kisiler();
                    $kisiModel->kurum = $customer->i_name;
                    $kisiModel->kisi_eposta = $customer->email;
                    $kisiModel->kisi_sinifi = "Misafir";
                    $kisiModel->kisi_gruplari = [
                        $customer->i_name . ' (Misafir)'
                    ];
                    $kisiModel->kisi_adi = $customer->name;
                    $kisiModel->kisi_soyadi = $customer->surname;
                    $kisiModel->kurum_kayit_num = $customer->id_number;
                    $kisiModel->kurum_numarasi = 'GRS' . ((string) $customer->_id);
                    $kisiModel->online_odeme_kapali = false;
                } else {
                    
                    if($kisiModel->kurum != $customer->i_name){
                        return ['status' => 'error', 'message' => 'Bilgileriniz '.$kisiModel->kurum.' kurumunda kayıtlı görünmektedir. Lütfen farklı e-posta adresi giriniz!'];
                    } else {                    
                        if ($kisiModel->kisi_eposta != $customer->email) {
                            $kisilerObj->kisiEpostaDegis($kisiModel->kisi_eposta, $customer->email);
                        }
                    }
                }

                if (!$kisiModel->save()) {
                    return ['status' => 'error', 'message' => 'Bilgilerinizde kişi kaydı açılamadı!'];
                }


                //kart kaydı yap
                $cardNum = (string) $customer->_id;
                $addNewCard = false;
                $kartObj = new Kartlar();
                $cardInfos = $kartObj->kartVarMi($cardNum);
                if (empty($cardInfos)) {
                    $addNewCard = true;
                } else {
                    if ($cardInfos->kart_turu != \app\modules\yonetim\models\KartTurleri::KAGIT_BILET) {
                        $addNewCard = true;
                    }
                }


                if ($addNewCard) {
                    $kartModel = new Kartlar();
                    $kartModel->kart_num = $cardNum;
                    $kartModel->kurum = $customer->i_name;
                    $kartModel->kart_turu = \app\modules\yonetim\models\KartTurleri::KAGIT_BILET;
                    $kartModel->kart_durumu = Kartlar::STOKTA;
                    if ($kartModel->save()) {
                        $kisiKModel = new KisiKartlari();
                        $kisiKModel->kart_num = $kartModel->kart_num;
                        $kisiKModel->kisi = $customer->email;
                        $kisiKModel->kisi_kart_durumu = KisiKartlari::ETKIN;
                        if (!$kisiKModel->save()) {
                            return ['status' => 'error', 'message' => 'Bilgilerinizde kişi kart kaydı açılamadı!'];
                        }
                    } else {
                        return ['status' => 'error', 'message' => 'Bilgilerinizde kart kaydı açılamadı!', 'errors' => $kartModel->getErrors()];
                    }
                }



                return ['status' => 'success', 'message' => 'Bilgileriniz başarıyla güncellendi.'];
            }

            return ['status' => 'error', 'message' => 'Bilgiler güncellenemedi.', 'errors' => $customer->errors];
        }

        return ['status' => 'error', 'message' => 'Geçersiz istek.'];
    }

}
