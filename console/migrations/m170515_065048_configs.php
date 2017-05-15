<?php

use yii\db\Migration;

class m170515_065048_configs extends Migration
{
    public function up()
    {
        $this->createTable('configs', [
            'id' => $this->primaryKey(),
            'alias' => $this->string()->notNull()->unique(),
            'name' => $this->string()->notNull()->unique(),
            'value' => $this->string()->notNull()
        ]);
    }

    public function down()
    {
        $this->dropTable('configs');
    }
}
