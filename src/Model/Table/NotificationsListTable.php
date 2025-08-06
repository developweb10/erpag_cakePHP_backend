<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class NotificationsListTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('notifications_list'); // Optional if table name is `users`
    }
}