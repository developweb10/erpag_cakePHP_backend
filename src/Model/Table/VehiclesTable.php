<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class VehiclesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('vehicles'); // Optional if table name is `users`
    }
}