<?php

namespace common\models\extended;

use yii;
use common\models\AppModel;

class Settings extends \common\models\Settings
{
    use AppModel;

    public static function getValueByAlias($alias)
    {
        $config = self::findOne(['alias' => $alias]);
        return $config ? $config->value : null;
    }

    public static function getList()
    {
        $arr = [];

        $configs = self::find()->all();

        foreach ($configs as $config) {
            $arr[$config->name] = $config->value;
        }

        return $arr;
    }
}
