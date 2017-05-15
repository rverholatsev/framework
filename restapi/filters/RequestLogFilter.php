<?php

namespace restapi\filters;

use yii;
use yii\rest\Controller;
use common\models\RequestsLogs;

class RequestLogFilter extends yii\base\ActionFilter
{
    public function afterAction($action, $result)
    {
        $request_log_id = Yii::$app->session->get('request_log_id', false);
        if ($request_log_id) {
            $requests_logs = RequestsLogs::findOne($request_log_id);
            $requests_logs->response = yii\helpers\Json::encode($result);
            $requests_logs->save();
            Yii::$app->session->remove('request_log_id');
        }
        return $result;
    }

    public function beforeAction($action)
    {
        $input = $action->controller->input;
        $user = Yii::$app->user;
        $requests_logs = new RequestsLogs();
        $requests_logs->controller = $action->controller->uniqueId;
        $requests_logs->action = $action->id;
        if (!empty($input['photo'])) {
            unset($input['photo']);
        }
        if (!empty($input['device'])) {
            $requests_logs->device = yii\helpers\Json::encode(base64_decode($input['device']));
            unset($input['device']);
        }
        $requests_logs->method = Yii::$app->request->method;
        if (count($input)) {
            $requests_logs->request = yii\helpers\Json::encode($input);
        }
        if (!($user->isGuest)) {
            $requests_logs->user_id = $user->id;
        }
        $requests_logs->response = 'failed';
        $requests_logs->timestamp = date('Y-m-d H:i:s');
        $requests_logs->save();
        Yii::$app->session->set('request_log_id', $requests_logs->id);
        return parent::beforeAction($action);
    }
}
