<?php
namespace restapi\controllers;
use Yii;
use yii\rest\Controller;
/**
 * App controller
 */
class AppController extends Controller
{
    /**
     * @var array
     */
    public $input;
    public $requestLogId;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function init()
    {
        $this->input = Yii::$app->request->isGet ? Yii::$app->request->get() : Yii::$app->request->bodyParams;
        parent::init();
    }

}