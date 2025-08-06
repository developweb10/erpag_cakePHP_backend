<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class DriverShiftsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('driver_shifts'); // Optional if table name is `users`
    }
}