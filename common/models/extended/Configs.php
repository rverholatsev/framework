<?php

namespace common\models\extended;

use yii;

class Configs extends \common\models\Configs
{
    public static function getValueByAlias($alias)
    {
        $config = self::findOne(['alias' => $alias]);
        return $config ? $config->value : null;
    }

    public static function getAllNamesValues()
    {
        $arr = [];

        $configs = self::find()->all();

        foreach ($configs as $config) {
            $arr[$config->name] = $config->value;
        }

        return $arr;
    }
}
