<?php

namespace backend\models;

use yii;
use common\models\extended\Admins;

class ChangePassword extends yii\base\Model
{
    public $password;
    private $_user;

    public function rules()
    {
        return [
            ['password', 'required'],
            ['password', 'string'],
        ];
    }

    public function changePassword()
    {
        $user = $this->getUser();
        $user->setPassword($this->password);
        if (!$user->save()) {
            throw new yii\base\NotSupportedException('Failed to save new password for unknown reason.');
        }
        return new \stdClass();
    }

    /** @return Admins|null */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Yii::$app->user->identity;
        }

        return $this->_user;
    }
}