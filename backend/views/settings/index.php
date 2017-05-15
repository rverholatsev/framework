<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\SearchSettings */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="configs-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'alias',
            'value',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update}',
            ],
        ],
    ]); ?>
</div>
