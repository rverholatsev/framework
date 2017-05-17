<?php

namespace restapi\modules\v1\controllers;

use common\models\FcmTokens;
use restapi\controllers\AppController;
use restapi\models\forms\FcmToken;
use restapi\models\forms\GenerateIdentityToken;
use restapi\models\forms\IdentityToken;
use restapi\models\forms\InviteUser;
use restapi\models\forms\Signin;
use restapi\models\forms\SignToStore;
use restapi\models\forms\UploadImage;
use restapi\models\forms\UserId;
use restapi\models\forms\UserSearch;
use restapi\models\forms\Verify;
use restapi\models\Users;
use restapi\models\Connections;
use yii;
use \restapi\filters\RequestLogFilter;

class EntytiesController extends AppController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['logs'] = ['class' => RequestLogFilter::className(), 'except' => ['log']];
        $behaviors['authenticator'] = [
            'class' => yii\filters\auth\HttpBearerAuth::className(),
            'except' => [
                'signup',
                'signin',
                'confirm',
            ],
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'index' => ['get'],
        ];
    }

    public function actionIndex()
    {

    }
}