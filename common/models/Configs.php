<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "configs".
 *
 * @property integer $id
 * @property string $alias
 * @property string $name
 * @property string $value
 */
class Configs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'configs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['alias', 'name', 'value'], 'required'],
            [['alias', 'name', 'value'], 'string', 'max' => 255],
            [['alias'], 'unique'],
            [['name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'alias' => 'Alias',
            'name' => 'Name',
            'value' => 'Value',
        ];
    }
}
