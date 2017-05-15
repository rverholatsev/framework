<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Settings */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="configs-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= Html::tag('b', 'ID') ?>
    <?= Html::tag('p', $model->id) ?>

    <?= Html::tag('br') ?>

    <?= Html::tag('b', 'Alias') ?>
    <?= Html::tag('p', $model->alias) ?>

    <?= Html::tag('br') ?>

    <?= $form->field($model, 'value')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
