<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Response;
use app\modules\ticket_sales\models\Tickets;

class TicketController extends \app\modules\ticket_sales\components\BaseController {

    public function actionIndex() {
        $customerId = Yii::$app->session->get('customer_id');

        if (!$customerId) {
            return $this->redirect(['/ticket_sales/auth/login']);
        }

        return $this->render('index');
    }

    public function actionList() {
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

    public function actionGetTickets() {
        
        Yii::$app->response->format = Response::FORMAT_JSON;

        $customerId = Yii::$app->session->get('customer_id');
         if (!$customerId) {
            return ['status' => 'error', 'message' => 'Giriş yapmalısınız.'];
        }
        $tickets = Tickets::find()->where(['customer_id' => $customerId])->all();

        $ticketData = [];
        foreach ($tickets as $ticket) {
            $ticketData[] = [
                'id' => (string) $ticket->_id,
                'route' => $ticket->route,
                'price' => $ticket->price,
                'date' => date('d.m.Y', strtotime($ticket->purchase_date)),
                'qr_code' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$ticket->qr_code}",
                'is_used' => $ticket->status == 'used' ? true : false
            ];
        }

        return [
            'status' => 'success',
            'tickets' => $ticketData
        ];
    }

   

}
