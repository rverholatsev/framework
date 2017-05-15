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
            'signup' => ['post'],
            'update' => ['patch'],
            'signin' => ['post'],
            'confirm' => ['post'],
            'invite' => ['post'],
            'upload-image' => ['post'],
            'sign-to-store' => ['post'],
            'search' => ['get'],
            'generate-identity-token' => ['post'],
            'send-fcm-token' => ['post'],
            'logout' => ['get'],
        ];
    }

    public function actionIndex()
    {
        /** @var Users $user */
        if ($get = Yii::$app->request->get()) {
            $model = new UserId();
            $model->load($get, '');
            if ($model->validate()) {
                $user = Users::findOne(['id' => $model->id]);
                return $user->userResponse(false, true, false);
            } else {
                return $model;
            }
        } else {
            $user = Yii::$app->user->identity;
            $userResponse = $user->userResponse(false, false, false);
            return $userResponse;
        }
    }

    /**
     * @return mixed
     * @throws yii\web\NotFoundHttpException
     */
    public function actionSignin()
    {
        $model = new Signin();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            return Users::signIn($model->_phone);
        }
        return $model;
    }

    public function actionConfirm()
    {
        $phone = Yii::$app->request->post('_phone');
        $model = new Verify($phone);
        $model->load(Yii::$app->request->post(), '');
        if (!in_array($model->_phone, array_keys(Yii::$app->params['testerPhones']))) {
            if (!$model->validate()) {
                return $model;
            }
        }
        /** @var Users $identity */
        $identity = Users::findByUnverifyPhone($phone);
        Yii::$app->user->login($identity);

        return $identity->confirm();
    }

    public function actionUpdate()
    {
        /** @var Users $user */
        $user = Yii::$app->user->identity;
        return $user->selfPatch(Yii::$app->request->post());
    }

    public function actionUploadImage()
    {
        /** @var Users $user */
        $user = Yii::$app->user->identity;
        $uploadImage = new UploadImage();
        $uploadImage->load(Yii::$app->request->post(), '');

        if ($uploadImage->validate()) {
            return $user->uploadImage($uploadImage->photo, $uploadImage->type);
        } else {
            return $uploadImage;
        }
    }

    public function actionInvite()
    {
        /** @var Users $user */
        $user = Yii::$app->user->identity;
        $invite = new InviteUser();
        $invite->load(Yii::$app->request->post(), '');

        if ($invite->validate()) {
            return $user->inviteNewUser($invite);
        } else {
            return $invite;
        }
    }

    public function actionSearch()
    {
        $userSearch = new UserSearch();
        $userSearch->load(Yii::$app->request->get(), '');
        if ($userSearch->validate()) {
            return Users::search($userSearch->text);
        } else {
            return $userSearch;
        }
    }

    public function actionGenerateIdentityToken()
    {
        /** @var Users $user */
        $model = new GenerateIdentityToken();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            return Users::generateIdentityToken($model->nonce);
        } else {
            return $model;
        }
    }

    public function actionSendFcmToken()
    {
        $model = new FcmToken();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            /** @var Users $user */
            $user = Yii::$app->user->identity;
            return $user->setFcmToken($model->token);
        } else {
            return $model;
        }
    }

    public function actionLogout()
    {
        /** @var Users $user */
        $_user = Yii::$app->user->identity;
        $user = Users::findOne($_user->id);

        $user->generateBearerToken();
        $user->save();

        FcmTokens::deleteAll(['user_id' => $user->id]);

        Yii::$app->user->logout();

        $response = Yii::$app->getResponse();
        $response->setStatusCode(200);
        return new \stdClass();
    }
}