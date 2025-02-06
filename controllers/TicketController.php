<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\ticket_sales\models\Ticket;
use app\modules\ticket_sales\models\TicketBuyers;

class TicketController extends Controller
{
    public function actionPurchase()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->session->has('customer_id')) {
            return ['status' => 'error', 'message' => 'Önce giriş yapmalısınız.'];
        }
        
        $request = Yii::$app->request;
        $bodyParams = json_decode($request->getRawBody(), true);
        
        $route = $bodyParams['route'] ?? null;
        $date = $bodyParams['date'] ?? null;
        
        if (!$route || !$date) {
            return ['status' => 'error', 'message' => 'Güzergah ve tarih zorunludur.'];
        }
        
        $buyerId = Yii::$app->session->get('customer_id');
        $ticket = new Ticket();
        $ticket->buyer_id = $buyerId;
        $ticket->route = $route;
        $ticket->purchase_date = date('Y-m-d H:i:s');
        $ticket->used_date = null;
        $ticket->save();
        
        return ['status' => 'success', 'message' => 'Bilet satın alındı.'];
    }
}
