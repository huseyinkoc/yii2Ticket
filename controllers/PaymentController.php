<?php

namespace app\modules\ticket_sales\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Tickets;
use app\models\Payments;
use app\models\TicketBuyers;
use app\modules\yonetim\models\Kurumlar;
use app\modules\yonetim\models\UlasimUcretleri;
use app\modules\yonetim\models\Sanalposlar;

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
                    $ticket->auth_code = $confirmVeri->authCode;
                    $ticket->hostlog_key = $confirmVeri->hostlogkey;
                    $ticket->qr_code = $this->generateQrCode($ticket);

                    if ($ticket->save()) {
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

    // 💳 3. Ödeme İşlemi ve 3D Secure Entegrasyonu
    public function actionMakePayment() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request->post();

        // Kart bilgileri ve fiyat kontrolü
        $cardHolder = $request['card_holder'];
        $cardNumber = $request['card_number'];
        $expiryDate = $request['expiry_date'];
        $cvv = $request['cvv'];
        $price = $request['price'];
        $routeName = $request['route_name'];

        // 🔐 3D Secure İşlemi Simülasyonu
        $paymentStatus = $this->process3DSecure($cardNumber, $cvv, $price);

        if ($paymentStatus === 'success') {
            // 🎟️ Bilet Oluşturma
            $ticket = new Tickets();
            $ticket->route_name = $routeName;
            $ticket->price = $price;
            $ticket->buyer_id = Yii::$app->session->get('customer_id');
            $ticket->qr_code = $this->generateQrCode();
            $ticket->created_at = date('Y-m-d H:i:s');
            $ticket->save();

            return [
                'status' => 'success',
                'message' => 'Ödeme başarılı!',
                'ticket_id' => $ticket->id,
                'qr_code' => $ticket->qr_code,
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Ödeme başarısız. Lütfen tekrar deneyin.',
            ];
        }
    }

    // 🧪 4. 3D Secure Simülasyonu (Gerçek Banka API'si ile entegre edilebilir)
    private function process3DSecure($cardNumber, $cvv, $price) {
        // Burada 3D Secure API ile gerçek entegrasyon yapılabilir.
        // Şimdilik başarılı bir ödeme simüle ediyoruz.
        return 'success';
    }

    // 🎟️ 5. Benzersiz QR Kod Üretimi
    private function generateQrCode($ticket) {
        $prefix = "TKT-"; // QR kodlarının başına eklenecek tanımlayıcı
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
