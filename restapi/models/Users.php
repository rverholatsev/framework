<?php

namespace restapi\models;

use common\components\Layer\Layer;
use common\components\Layer\LayerIdentityTokenProvider;
use common\components\Notifier;
use common\models\extended\RequestsLogs;
use common\models\FcmTokens;
use restapi\models\forms\FcmToken;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;


/**
 * Class Users
 * @package restapi\models
 *
 * @property Users[] $connectedUsers
 * @property Connections[] $connections
 * @property Stores[] $stores
 * @property UsersStores[] $usersStores
 */
class Users extends \common\models\extended\Users implements \yii\web\IdentityInterface
{
    public function getConnections()
    {
        return Connections::find()
            ->where(['host_id' => $this->id])
            ->orWhere(['object_id' => $this->id])
            ->all();
    }

    public function getStores()
    {
        return $this->hasMany(Stores::className(), ['id' => 'store_id'])->viaTable('users_stores', ['user_id' => 'id']);
    }

    public function getUsersStores()
    {
        return $this->hasMany(UsersStores::className(), ['user_id' => 'id'])->inverseOf('user');
    }

    public $newRecord = null;

    public function beforeSave($insert)
    {
        ($this->isNewRecord) ? $this->newRecord = true : $this->newRecord = false;

        return parent::beforeSave($insert);
    }

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['bearer_token' => $token]);
    }

    public static function findByPhone($phone)
    {
        return static::findOne(['phone' => $phone, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByUnverifyPhone($phone)
    {
        return static::find()
            ->where(['phone' => $phone, 'status' => [self::STATUS_UNVERIFY, self::STATUS_ACTIVE]])
            ->limit(1)->one();
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function getBearerToken()
    {
        return $this->bearer_token;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function generateBearerToken()
    {
        $this->bearer_token = Yii::$app->security->generateRandomString();
    }

    public function generateCodeVerify()
    {
        $this->code_verify = (string)mt_rand(1000, 9999);
    }

    public static function fetchAll()
    {
        $query = self::find()->where(['id' => Yii::$app->user->id, 'status' => self::STATUS_ACTIVE])->orderBy('created_at DESC');
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    public static function signIn($phone)
    {
        $user = self::findByUnverifyPhone($phone);
        if (empty($user)) {
            $user = new self;
            $validator = new \yii\validators\NumberValidator();

            // testing
            if (in_array($phone, array_keys(Yii::$app->params['testerPhones']))) {
                $user->phone = $phone;
            } else {
                if ($validator->validate($phone)) {
                    $user->phone = $phone;
                }
            }

            $user->generateAuthKey();
            $user->generateBearerToken();
            $user->status = Users::STATUS_UNVERIFY;
        }

        // testing
        if (in_array($phone, ['79999999999', '19999999999', '77777777777', '17777777777'])) {
            $user_id = $user->id;
            $user->delete();

            $user = new self;
            $user->id = $user_id;
            $user->phone = $phone;
            $user->generateAuthKey();
            $user->generateBearerToken();
            $user->status = Users::STATUS_UNVERIFY;
        }

        if (in_array($phone, array_keys(Yii::$app->params['testerPhones']))) {
            $user->code_verify = Yii::$app->params['testerPhones'][$phone];
        } else {
            $user->generateCodeVerify();
        }

        if ($user->save()) {
            // testing
            if (!in_array($phone, array_keys(Yii::$app->params['testerPhones']))) {
                Notifier::sendSms($phone, 'Your verification code for ClockedIn: ' . $user->code_verify);
            }
            $response = Yii::$app->getResponse();
            $response->setStatusCode($user->newRecord ? 201 : 200);
            return new \stdClass();
        } elseif (!$user->hasErrors()) {
            throw new \yii\web\ServerErrorHttpException('Failed to create the object for unknown reason.');
        } else {
            return $user->getErrors();
        }
    }

    public function confirm()
    {
//        $this->code_verify = null;
        $this->status = self::STATUS_ACTIVE;
        $this->role = 'user';
        $this->save();
        return $this->userResponse(false, false);
    }

    public function selfPatch($values)
    {
        $this->email = isset($values['email']) ? $values['email'] : $this->email;
        $this->first_name = isset($values['first_name']) ? $values['first_name'] : $this->first_name;
        $this->last_name = isset($values['last_name']) ? $values['last_name'] : $this->last_name;

        if (!$this->save()) {
            throw new \yii\web\ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        return $this->userResponse(false, false, false);
    }

    public function getApprovedConnectedUsers()
    {
        $connections = $this->connections;

        $users = [];
        foreach ($connections as $connection) {
            if (!$connection->is_approved) {
                continue;
            }

            $users[] = ($connection->host_id == $this->id
                ? $connection->object
                : $connection->host);
        }

        return $users;
    }

    public function userResponse($isHideConnections = false, $isHideTokens = true, $isHideStores = true)
    {
        $result = new \stdClass();
        $result->id = (string)$this->id;
        $result->avatar = $this->getImageUrl(self::AVATAR_DIR);
        $result->background = $this->getImageUrl(self::BACKGROUND_DIR);
        $result->first_name = $this->first_name;
        $result->last_name = $this->last_name;
        $result->phone = $this->phone;
        $result->email = $this->email;
        $result->experience = $this->experience;
        $result->status = $this->status;
        $result->created_at = $this->created_at;
        $result->updated_at = $this->updated_at;
        $result->profile_filled = $this->isProfileFilled();
        $result->bearer_token = $isHideTokens ? null : $this->bearer_token;
        $result->identity_token = $isHideTokens ? null : $this->identity_token;

        $result->connections = [];
        if (!$isHideConnections) {
            foreach ($this->getApprovedConnectedUsers() as $connectedUser) {
                $result->connections[] = $connectedUser->userResponse(true, true, true);
            }
        }

        $result->stores = [];
        if (!$isHideStores) {
            foreach ($this->usersStores as $usersStores) {
                /** @var UsersStores $usersStores */
                $result->stores[] = [
                    'store' => $usersStores->store->storeResponse(),
                    'role' => UsersStores::ROLES[$usersStores->role],
                    'is_approved' => ($usersStores->state == UsersStores::STATE_APPROVED)
                ];
            }
        }
        return $result;
    }

    public function inviteNewUser(forms\InviteUser $invite)
    {
        if (self::findOne(['phone' => $invite->_phone])) {
            throw new \yii\web\BadRequestHttpException('User with this phone number already exists');
        }
        $name = $invite->name ? " $invite->name" : '';
        // ToDo: добавить ссылку для ios и android устройств. Можно использовать сервис Branch.io
        Notifier::sendSms($invite->_phone, "Hello$name. Your invite");
        return new \stdClass();
    }

    public static function search($text)
    {
        $curUserId = Yii::$app->user->identity->id;

        $users = [];
        foreach (Users::find()->all() as $user) {
            /** @var Users $user */
            if ($user->id == $curUserId) {
                continue;
            }

            if(stripos($user->first_name, $text) !== false
                || stripos($user->last_name, $text) !== false){
                $users[] = $user;
                continue;
            }

            $storesIds = [];
            foreach ($user->usersStores as $usersStores) {
                $storesIds[] = $usersStores->store_id;
            }

            $storesBrands = Stores::find()
                ->leftJoin('brands', 'stores.brand_id = brands.id')
                ->where(['`stores`.`id`' => $storesIds])
                ->andWhere(['OR',
                    ['like', 'stores.address', $text],
                    ['like', 'brands.name', $text],
                ])
                ->all();

            if(!empty($storesBrands)){
                $users[] = $user;
                continue;
            }
        }

        $usersResponses = [];
        foreach ($users as $user) {
            /** @var self $user */
            $usersResponses[] = $user->userResponse();
        }
        return $usersResponses;
    }

    public function _getConnections()
    {
        $userStores = UsersStores::find()
            ->select('store_id')
            ->where(['user_id' => $this->id])
            ->column();

        $coworkers = Users::find()
            ->innerJoin('users_stores', '`users_stores`.`user_id` = `users`.`id`')
            ->where(['!=', '`users`.`id`', $this->id])
            ->andWhere(['`users_stores`.`store_id`' => $userStores])
            ->all();

        $connections = Connections::find()
            ->where(['or', 'host_id = ' . $this->id, 'object_id = ' . $this->id])
            ->andWhere(['is_approved' => true])
            ->all();

        $results = [];
        foreach ($coworkers as $coworker) {

            /** @var Users $coworker */

            $isAdded = false;
            foreach ($connections as $index => $connection) {

                /** @var Connections $connection */

                // если работаем вместе и друзья
                if ($connection->host_id == $coworker->id || $connection->object_id == $coworker->id) {
                    $results[] = [
                        'user' => $coworker->userResponse(true),
                        'is_connect' => true,
                    ];
                    unset($connections[$index]);
                    $isAdded = true;
                    break;
                }
            }

            // если рабоатем вместе но не друзья
            if (!$isAdded) {
                $results[] = [
                    'user' => $coworker->userResponse(true),
                    'is_connect' => false,
                ];
            }
        }

        // если друзья
        foreach ($connections as $connection) {
            $user = $this->id == $connection->host_id
                ? Users::findOne(['id' => $connection->object_id])
                : Users::findOne(['id' => $connection->host_id]);

            $results[] = [
                'user' => $user->userResponse(true),
                'is_connect' => true,
            ];
        }

        return $results;
    }

    public function getInvites()
    {
        $users = Users::find()
            ->innerJoin('connections', '`connections`.`host_id` = `users`.`id`')
            ->where(['connections.object_id' => $this->id, 'connections.is_approved' => false])
            ->all();

        $results = [];
        foreach ($users as $user) {
            $results[] = $user->userResponse();
        }

        return $results;
    }

    public static function generateIdentityToken($nonce)
    {
        /** @var Users $user */
        $user = Yii::$app->user->identity;

        $layerIdentityTokenProvider = new LayerIdentityTokenProvider();
        $layerIdentityTokenProvider->setProviderID(Yii::$app->params['layer']['provider_id']);
        $layerIdentityTokenProvider->setKeyID(Yii::$app->params['layer']['key_id']);
        $layerIdentityTokenProvider->setPrivateKey(Yii::$app->params['layer']['private_key']);
        $layerIdentityTokenProvider->setAdditionalParameters([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->first_name . ' ' . $user->last_name,
            'avatar_url' => $user->getImageUrl(self::AVATAR_DIR, $user->avatar),
            'phone_number' => $user->phone,
            'email_address' => $user->email,
        ]);
        $identityToken = $layerIdentityTokenProvider->generateIdentityToken($user->id, $nonce);

        $user->identity_token = $identityToken;

        if ($user->save()) {
            return ['identity_token' => $identityToken];
        } else {
            throw new ServerErrorHttpException('Error saving Identity Token.');
        }
    }

    public function setFcmToken($token)
    {
        if (!FcmTokens::findOne(['user_id' => $this->id, 'token' => $token])) {
            $fcmToken = new FcmTokens();
            $fcmToken->user_id = $this->id;
            $fcmToken->token = $token;
            if ($fcmToken->save()) {
                $response = Yii::$app->getResponse();
                $response->setStatusCode(200);
                return new \stdClass();
            } else {
                throw new InternalErrorException('Can\'t save FCM token.');
            }
        } else {
            throw new BadRequestHttpException('FCM token is already exist');
        }
    }

    public function sendPush($notification, $data)
    {
        $tokens = FcmTokens::find()
            ->select(['token'])
            ->where(['user_id' => $this->id])
            ->column();

        if (count($tokens) == 0) {
            RequestsLogs::setError(['Push Notification not sended. Host haven\'t FCM tokens.']);
        } else {
            Notifier::sendPush($tokens, $notification, $data);
        }
    }

    // TODO delete method realized in Stores
    public function getLeaders($store_id)
    {
        if (!$usersStores = $this->getUsersStoresByStoreId($store_id)) {
            return null;
        }

        $role = $this->getUsersStoresByStoreId($store_id)->role;
        $store = Stores::findOne($store_id);

        if ($role == UsersStores::ROLE_EMPLOYEE) {
            $managers = $store->getUsersByRole(UsersStores::ROLE_MANAGER);
            if (count($managers) > 0) {
                return $managers;
            } elseif (count($owners = $store->getUsersByRole(UsersStores::ROLE_OWNER)) > 0) {
                return $owners;
            }
        } elseif ($role == UsersStores::ROLE_MANAGER) {
            if (count($owners = $store->getUsersByRole(UsersStores::ROLE_OWNER)) > 0) {
                return $owners;
            }
        }
        return [];
    }

    // TODO delete method realized in Stores
    public function getWorkers($store_id, $is_approved = null)
    {
        // by default $role = UsersStores::ROLE_EMPLOYEE, it means that user is a manager

        $user_role = UsersStores::findOne(['user_id' => $this->id])->role;
        $store = Stores::findOne($store_id);

        // if user is a manager get employees
        if ($user_role == UsersStores::ROLE_MANAGER) {
            return isset($is_approved)
                ? $store->getUsersByUsersStoresParams([
                    'role' => UsersStores::ROLE_EMPLOYEE,
                    'is_approved' => $is_approved
                ])
                : $store->getUsersByUsersStoresParams([
                    'role' => UsersStores::ROLE_EMPLOYEE,
                ]);

            // if user is a owner
        } elseif ($user_role == UsersStores::ROLE_OWNER) {
            $role = [UsersStores::ROLE_EMPLOYEE, UsersStores::ROLE_MANAGER];

            $managers = $store->getUsersByUsersStoresParams(['role' => UsersStores::ROLE_MANAGER]);
            if (count($managers) > 0) {
                $role = UsersStores::ROLE_MANAGER;
            }

            return isset($is_approved)
                ? $store->getUsersByUsersStoresParams([
                    'role' => $role,
                    'is_approved' => $is_approved
                ])
                : $store->getUsersByUsersStoresParams([
                    'role' => $role,
                ]);
        }
        return [];
    }

    /** @return UsersStores */
    public function getUsersStoresByStoreId($store_id)
    {
        return UsersStores::findOne(['store_id' => $store_id, 'user_id' => $this->id]);
    }
}
