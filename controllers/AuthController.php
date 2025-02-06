<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\Response;
use app\modules\ticket_sales\services\AuthService;

class AuthController extends \app\modules\ticket_sales\components\BaseController {

    //public $enableCsrfValidation = false;


    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'send-phone-code' => ['POST'],
                    'send-email-code' => ['POST'],
                    'verify-code' => ['POST'],
                ],
            ],
        ];
    }

    public function actionLogin() {
        
        if (!Yii::$app->session->has('customer_id')) {
           
            return $this->render('index', [
                'csrfToken' => Yii::$app->request->csrfToken,
            ]);
            
        }        
        
        
        return $this->redirect(['/ticket_sales/customer/index'])->send();
    }

    public function actionSendPhoneCode()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $bodyParams = json_decode($request->getRawBody(), true);
        
        $phone = $bodyParams['phone'] ?? null;
        if (!$phone || !preg_match('/^\+90\d{10}$/', $phone)) {
            return ['status' => 'error', 'message' => 'Geçersiz telefon numarası'];
        }

        $authService = new AuthService();
        $result = $authService->sendPhoneCode($phone);

        return $result;
    }
    
    public function actionSendEmailCode()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $bodyParams = json_decode($request->getRawBody(), true);
        
        $email = $bodyParams['email'] ?? null;

        $authService = new AuthService();
        $result = $authService->sendEmailCode($email);

        return $result;
    }
   

    public function actionVerifyCode()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $bodyParams = json_decode($request->getRawBody(), true);

        $code = $bodyParams['code'] ?? null;
        if (!$code) {
            return ['status' => 'error', 'message' => 'Doğrulama kodu boş olamaz'];
        }

        $authService = new AuthService();
        $result = $authService->verifySmsCode($code);

        return $result;
    }
    
    public function actionLogout() {
        
        $authService = new AuthService();
        $authService->logout();
        
        return $this->redirect(['login'])->send();
    }

}
