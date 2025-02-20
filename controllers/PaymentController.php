<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\modules\ticket_sales\models\Tickets;
use app\models\Payments;
use app\modules\ticket_sales\models\TicketBuyers;
use app\modules\yonetim\models\Kurumlar;
use app\modules\yonetim\models\UlasimUcretleri;
use app\modules\yonetim\models\Sanalposlar;
use app\modules\yonetim\models\FaturaliYuklemeler;

class PaymentController extends Controller {
// CSRF korumasını belirli actionlarda devre dışı bırak
//    public function behaviors()
//    {
//        return [
//            'csrf' => [
//                'class' => \yii\filters\CsrfFilter::class,
//                'except' => ['payment-response'], // Banka dönüşü için CSRF kapalı
//            ],
//        ];
//    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        if ($action->id == 'payment-response') {
            $this->enableCsrfValidation = false;
        } else {
            $this->enableCsrfValidation = true;
        }

        return parent::beforeAction($action);
    }

    public function actionInitiatePayment() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // JSON formatında gelen veriyi almak için
        $postData = json_decode(Yii::$app->request->getRawBody(), true);

        $sanalPoslarObj = new Sanalposlar();
        $spBilgileri = $sanalPoslarObj->SPAdDNSPBilgileri('YAPIKREDI');

        $uUcretleriObj = new UlasimUcretleri();
        $ulasimUcretBilgisi = $uUcretleriObj->ulasimUcreti($postData['route_name']);

        if (!$ulasimUcretBilgisi) {
            return [
                'status' => 'error',
                'message' => 'Geçersiz güzergah seçimi.'
            ];
        }

        $tutar = 1 * 100; //$ulasimUcretBilgisi->kartsiz_ucret * 100;
        // Sipariş kodu oluşturma (20 haneli): Müşteri ID + hash + rastgele sayı
        $customerId = Yii::$app->session->get('customer_id') ?? '0000';
        $hashedId = substr(md5($customerId), 0, 8); // 8 haneli hash
        $randomPart = str_replace('.', '0', substr(uniqid('', true), -12)); // Nokta olmadan 12 haneli benzersiz ID
        $oid = $hashedId . $randomPart; // Toplam 20 hane
        //echo $oid;exit();

        $carHolderName = $postData['card_holder'];
        $ccno = str_replace(' ', '', $postData['card_number']);
        $expDateParts = explode('/', $postData['expiry_date']);
        $expMonth = $expDateParts[0];
        $expYear = $expDateParts[1];
        $expDate = $expYear . $expMonth; // YYYYMM formatında birleştirildi
        $cvc = $postData['cvv'];
        $url = $spBilgileri->bilgi_6;

        $xml = "<?xml version='1.0' encoding='ISO-8859-9'?>
            <posnetRequest>
                <mid>{$spBilgileri->bilgi_1}</mid>
                <tid>{$spBilgileri->bilgi_2}</tid>
                <oosRequestData>
                    <posnetid>{$spBilgileri->bilgi_3}</posnetid>
                    <ccno>{$ccno}</ccno>
                    <expDate>{$expDate}</expDate>
                    <cvc>{$cvc}</cvc>
                    <amount>{$tutar}</amount>
                    <currencyCode>YT</currencyCode>
                    <installment>00</installment>
                    <XID>{$oid}</XID>
                    <cardHolderName>{$carHolderName}</cardHolderName>
                    <tranType>Sale</tranType>
                </oosRequestData>
            </posnetRequest>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "xmldata=" . $xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $veri = simplexml_load_string($response);
        //print_r($veri->oosRequestDataResponse->data1);exit();

        if ($veri && $veri->approved == 1) {

            $formHtml = $this->renderPartial('yapikredi_3d', [
                'bankUrl' => $spBilgileri->bilgi_7,
                'tutar' => $tutar,
                'oid' => $oid,
                'spBilgileri' => $spBilgileri,
                'data1' => $veri->oosRequestDataResponse->data1,
                'data2' => $veri->oosRequestDataResponse->data2,
                'sign' => $veri->oosRequestDataResponse->sign,
            ]);

            return [
                'status' => 'success',
                'html' => $formHtml
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Ödeme başlatılamadı.'
            ];
        }
    }

    public function actionPaymentResponse() {
        $postData = Yii::$app->request->post();
        $sanalPoslarObj = new Sanalposlar();
        $spBilgileri = $sanalPoslarObj->SPAdDNSPBilgileri('YAPIKREDI');

        $mId = $spBilgileri->bilgi_1;
        $tId = $spBilgileri->bilgi_2;
        $url = $spBilgileri->bilgi_6;

        $orderId = $postData['Xid'] ?? '';
        $amount = $postData['Amount'] ?? 0;
        $status = 'error';
        $message = 'Ödeme başarısız. Lütfen tekrar deneyiniz.';
        $ticket = null;

        if (!empty($postData)) {
            $resolveXml = "<?xml version='1.0' encoding='ISO-8859-9'?>
            <posnetRequest>
                <mid>{$mId}</mid>
                <tid>{$tId}</tid>
                <oosResolveMerchantData>
                    <bankData>{$postData['BankPacket']}</bankData>
                    <merchantData>{$postData['MerchantPacket']}</merchantData>
                    <sign>{$postData['Sign']}</sign>
                </oosResolveMerchantData>
            </posnetRequest>";

            $response = $this->sendCurlRequest($url, $resolveXml);
            $veri = simplexml_load_string($response);

            if ($veri && $veri->approved == 1 && $veri->oosResolveMerchantDataResponse->mdStatus == 1) {
                $confirmXml = "<?xml version='1.0' encoding='ISO-8859-9'?>
                <posnetRequest>
                    <mid>{$mId}</mid>
                    <tid>{$tId}</tid>
                    <oosTranData>
                        <bankData>{$postData['BankPacket']}</bankData>
                    </oosTranData>
                </posnetRequest>";

                $confirmResponse = $this->sendCurlRequest($url, $confirmXml);
                $confirmVeri = simplexml_load_string($confirmResponse);

                if ($confirmVeri && $confirmVeri->approved == 1) {
                    $ticket = new Tickets();
                    $ticket->customer_id = Yii::$app->session->get('customer_id');
                    $ticket->order_id = $orderId;
                    $ticket->price = $amount / 100;
                    $ticket->client_id = $mId;
                    $ticket->auth_code = $confirmVeri->authCode;
                    $ticket->hostlog_key = $confirmVeri->hostlogkey;
                    $ticket->qr_code = $this->generateQrCode($ticket);
                    $ticket->status = Tickets::ACTIVE;
                    $ticket->created_at = date('Y-m-d H:i:s');
                    $ticket->expires_at = date('Y-m-d H:i:s', strtotime('+'.Tickets::EXP_TIME.' hours')); // 24 saat sonra
                    
                    
                    $reponseTxt = 'Sipariş Num:' . $orderId;
                    $reponseTxt .= ', Türü:' . 'QR Bilet';
                    $reponseTxt .= ', ClientId:' . $mId;
                    $reponseTxt .= ', RefNum:' . $confirmVeri->hostlogkey;
                    $reponseTxt .= ', Onay Kodu:' . $confirmVeri->authCode;
                    $reponseTxt .= ', TUTAR:' . floatval($ticket->price);
                    
                    $ticket->response_txt = $reponseTxt;
                    

                    if ($ticket->save()) {
                        $cardNum = $ticket->customer_id;
                        
                        $ticketBuyerObj = TicketBuyers();
                        $email = $ticketBuyerObj->getEmailById($ticket->customer_id);
                        
                        $invoiceObj = new FaturaliYuklemeler();
                        $invoiceObj->faturaBilgisiKaydet($cardNum, $invoiceObj::FATURA_ISLEMI_YUKLEME, $invoiceObj::FATURA_ISLEM_TURU_QR_KAGIT_BILET, null, $mId, $orderId, $ticket->price, $email);
                        
                        
                        $status = 'success';
                        $message = "Ödeme başarıyla tamamlandı. Sipariş No: {$orderId}";
                    } else {
                        $message = "Bilet kaydedilemedi.";
                    }
                } else {
                    $message = "Ödeme onayı başarısız.";
                }
            } else {
                $message = "Banka doğrulaması başarısız.";
            }
        }

        return $this->render('payment_result', [
                    'status' => $status,
                    'message' => $message,
                    'ticket' => $ticket
        ]);
    }

    public function actionBuyTicket() {
        // Güzergah listesini alıyoruz
        $kurumlarObj = new Kurumlar();
        $iNumber = Yii::$app->session->get('i_name');
        $routes = $kurumlarObj->kurumBazliSUListesi($iNumber, true);
        //echo $iNumber;exit();
        //print_r($routes);exit();
        // Bilet satın alma sayfasını render ediyoruz
        return $this->render('buy_ticket', [
                    'routes' => $routes
        ]);
    }

    // 📍 1. Güzergah Listesini Getirme
    public function actionGetRoutes() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $iNumber = Yii::$app->session->get('i_name');
        $kurumlarObj = new Kurumlar();
        $routes = $kurumlarObj->kurumBazliSUListesi($iNumber, true);

        return [
            'status' => 'success',
            'routes' => $routes,
        ];
    }

    // 💰 2. Ücret Hesaplama
    public function actionGetPrice() {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $bodyParams = json_decode($request->getRawBody(), true);
        $routeName = $bodyParams['route_name'] ?? null;
        $uUcretleriObj = new UlasimUcretleri();
        $ulasimUcretBilgisi = $uUcretleriObj->ulasimUcreti($routeName);
        if ($ulasimUcretBilgisi) {
            return [
                'status' => 'success',
                'price' => $ulasimUcretBilgisi->kartsiz_ucret,
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Ücret bilgisi bulunamadı.',
            ];
        }
    }

    
    // 🎟️ 5. Benzersiz QR Kod Üretimi
    private function generateQrCode($ticket) {
        $prefix = "02TCK"; // QR kodlarının başına eklenecek tanımlayıcı
        return $prefix . md5($ticket->customer_id . $ticket->order_id . time());
    }

    private function sendCurlRequest($url, $xml) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "xmldata=" . $xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}
