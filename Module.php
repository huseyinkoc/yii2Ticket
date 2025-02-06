<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of Module
 *
 * @author Owner
 */
namespace app\modules\ticket_sales;

use Yii;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\ticket_sales\controllers';

    public function init()
    {
        parent::init();
        // Modüle özel layout'u tanımlıyoruz
        Yii::$app->layout = '@app/modules/ticket_sales/views/layouts/main';
    }
}
