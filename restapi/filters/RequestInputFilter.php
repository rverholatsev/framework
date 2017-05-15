<?php
namespace restapi\filters;
use yii;
use yii\base\DynamicModel;
use yii\rest\Controller;
class RequestInputFilter extends yii\base\Behavior
{
    public $rules = [];
    public $model;
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'validate',
        ];
    }
    /**
     * @param yii\base\ActionEvent $event
     * @return bool
     * @throws yii\web\HttpException
     */
    public function validate($event)
    {
        $action = $event->action->id;
        if (isset($this->rules[$action])) {
            $method = strtolower(Yii::$app->request->method);
            $input = $method == 'get' ? Yii::$app->request->get() : Yii::$app->request->bodyParams;
            $fields = $rules = [];
            foreach ($this->rules[$action] as $rule) {
                $first = $rule[0];
                $second = $rule[1];
                unset($rule[0], $rule[1]);
                $fieldsNames = is_array($first) ? $first : [$first];
                if (isset($rule['on'])) {
                    if ($rule['on'] == $method) {
                        $fields = array_merge($fields, $fieldsNames);
                    }
                } else {
                    $fields = array_merge($fields, $fieldsNames);
                    $rule['on'] = $method;
                }
                array_push($rules, [$fieldsNames, $second, $rule]);
            }
            $fields = array_unique($fields);
            if (!empty($fields)) {
                $this->model = new DynamicModel($fields);
                $this->model->scenario = $method;
                foreach ($rules as $rule) {
                    list($attributes, $validator, $options) = $rule;
                    $this->model->addRule($attributes, $validator, $options);
                }
                $this->model->load($input, '');
                if (!$this->model->validate()) {
                    // $event->handler|$event->data
//                    $event->isValid = false;
//                    $event->data = $this->model;
//                    $event->handled = true;
//                    $event->result = $this->model;
                    throw new yii\web\HttpException(422, json_encode($this->model->getErrors()));
//                    return $this->model;
                }
//                return  $this->model;
            }
        }
    }
}