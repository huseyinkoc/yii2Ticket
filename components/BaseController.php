<?php

namespace app\modules\ticket_sales\components;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class BaseController extends Controller
{
    /**
     * Giriş yapmadan erişilebilecek action'lar
     */
    protected $allowActions = ['login', 'send-phone-code', 'send-email-code', 'verify-code', 'forgot-password'];

    public function beforeAction($action)
    {
        if (!Yii::$app->session->has('customer_id') && !in_array($action->id, $this->allowActions)) {
            return $this->redirect(['/ticket_sales/auth/login'])->send();
        }
        return parent::beforeAction($action);
    }
}
