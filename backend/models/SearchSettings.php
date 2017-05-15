<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Settings;

/**
 * SearchConfigs represents the model behind the search form about `common\models\Configs`.
 */
class SearchSettings extends Settings
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['id', 'integer'],
            [['alias', 'value'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Settings::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'alias', $this->alias])
            ->andFilterWhere(['like', 'value', $this->value]);

        return $dataProvider;
    }
}
