<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersTableMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $userTable = $this->table('user');
        $userTable->addColumn('username', 'string', ['limit' => 20])
            ->addColumn('password', 'string', ['limit' => 256])
            ->addColumn('email', 'string', ['limit' => 100])
            ->addColumn('first_name', 'string', ['limit' => 30])
            ->addColumn('last_name', 'string', ['limit' => 30])
            ->addColumn('birthday', 'datetime')
            ->addColumn('createdAt', 'datetime')
            ->addColumn('updatedAt', 'datetime', ['null' => true])
            ->addColumn('createdBy', 'string',['limit' => 30])
            ->addColumn('updatedBy', 'string', ['null' => true, 'limit' => 30])
            ->addIndex(['username', 'email'], ['unique' => true])
            ->create();
    }
}
