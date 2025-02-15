<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\Response;
use app\modules\ticket_sales\services\AuthService;
use app\modules\yonetim\models\Kurumlar;
use yii\web\NotFoundHttpException; // 404 için gerekli sınıf

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

    public function actionLogin($in = "") {
        if (!Yii::$app->session->has('customer_id')) {
            if (!empty($in) || Yii::$app->session->has('i_number')) {
                $in = empty($in) ? Yii::$app->session->get('i_number') : $in;
                $kurumObj = new Kurumlar();
                $kurum = $kurumObj->KurumSistemNumDNKurum($in);

                if (!empty($kurum)) {
                    Yii::$app->session->set('i_number', $in);
                    Yii::$app->session->set('i_name', $kurum);
                    return $this->render('index', [
                       'csrfToken' => Yii::$app->request->csrfToken,
                       'in' => $in,
                    ]);
                } else {
                    // ❌ Geçersiz kurum için 404
                    throw new NotFoundHttpException('Geçersiz kurum bilgisi.');
                }
            } else {
                // ❌ cn boşsa 404
                throw new NotFoundHttpException('Sayfa bulunamadı.');
            }
        }

        return $this->redirect(['/ticket_sales/customer/index'])->send();
    }

    public function actionSendPhoneCode() {
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

    public function actionSendEmailCode() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $bodyParams = json_decode($request->getRawBody(), true);

        $email = $bodyParams['email'] ?? null;

        $authService = new AuthService();
        $result = $authService->sendEmailCode($email);

        return $result;
    }

    public function actionVerifyCode() {
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
        $result = $authService->logout();

        return $this->redirect(['login', 'in' => $result['i_number']])->send();
    }

}
