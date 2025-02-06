<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Controller;
use app\modules\ticket_sales\models\TicketBuyers;
use yii\web\Response;
use app\modules\ticket_sales\models\Tickets;

class CustomerController extends Controller {

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

    public function actionGetCustomer()
    {
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
                'phone' => $customer->phone
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

            if (empty($customer->email)) {
                $customer->email = $bodyParams['email'] ?? $customer->email;
            }

            if (empty($customer->phone)) {
                $customer->phone = $bodyParams['phone'] ?? $customer->phone;
            }

            if ($customer->validate() && $customer->save()) {
                return ['status' => 'success', 'message' => 'Bilgileriniz başarıyla güncellendi.'];
            }

            return ['status' => 'error', 'message' => 'Bilgiler güncellenemedi.', 'errors' => $customer->errors];
        }

        return ['status' => 'error', 'message' => 'Geçersiz istek.'];
    }

    public function actionViewTickets() {
        $customerId = Yii::$app->session->get('customer_id');

        if (!$customerId) {
            return $this->redirect(['/ticket_sales/auth/login']);
        }

        return $this->render('tickets');
    }

    public function actionTickets() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $customerId = Yii::$app->session->get('customer_id');
        if (!$customerId) {
            return ['status' => 'error', 'message' => 'Giriş yapmalısınız.'];
        }
        
        //echo $customerId;exit();

        $tickets = Tickets::find()->where(['customer_id' => $customerId])->asArray()->all();

        return [
            'status' => 'success',
            'tickets' => $tickets
        ];
    }

}
