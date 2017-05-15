<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => ['GlobalInit'],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'GlobalInit' => [
            'class' => 'common\components\GlobalInit',
        ],
    ],
];
