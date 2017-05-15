<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('admins', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'role' => 'enum("admin","superadmin") NOT NULL',
        ], $tableOptions);

        $this->insert('admins', [
            'username' => 'superadmin',
            'password_hash' => '$2y$13$rXaKpZXroNxAgsnRNbPpaeJ7rFQlV/55Ls2yJ7lSYmQLN.UB8QLTG',
            'role' => 'superadmin',
        ]);
    }

    public function down()
    {
        $this->dropTable('admins');
    }
}
