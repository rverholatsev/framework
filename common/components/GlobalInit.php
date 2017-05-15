<?php

namespace common\components;

use common\models\extended\Settings;
use yii;

class GlobalInit extends \yii\base\Component
{
    public function init()
    {
        \Yii::$app->params['globalSettings'] = Settings::getList();
        parent::init();
    }
}