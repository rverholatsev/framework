<?php

use yii\db\Migration;

class m170515_065048_settings extends Migration
{
    public function up()
    {
        $this->createTable('settings', [
            'id' => $this->primaryKey(),
            'alias' => $this->string()->notNull()->unique(),
            'name' => $this->string()->notNull()->unique(),
            'value' => $this->string()->notNull()
        ]);
    }

    public function down()
    {
        $this->dropTable('settings');
    }
}
