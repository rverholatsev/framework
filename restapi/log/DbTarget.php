<?php
namespace restapi\log;

use Yii;
use yii\helpers\VarDumper;
use yii\log\Target;
use common\models\RequestsLogs;

class DbTarget extends Target {
	public function init() {
		parent::init();
	}

	public function export() {
		$request_log_id = Yii::$app->session->get('request_log_id', false);
		if($request_log_id) {
			$messages = [];
			foreach($this->messages as $message) {
				list($text, $level, $category, $timestamp) = $message;
				if(!is_string($text)) {
					if($text instanceof \Throwable || $text instanceof \Exception) {
						$text = (string)$text;
					} else {
						$text = VarDumper::export($text);
					}
				}
				$messages[] = $text;
			}
			$requests_logs = RequestsLogs::findOne($request_log_id);
			$requests_logs->error = implode('<br />', $messages);
			$requests_logs->save();
			Yii::$app->session->remove('request_log_id');
		}
	}
}