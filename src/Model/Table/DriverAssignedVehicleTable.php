<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class DriverAssignedVehicleTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('drivers_assigned_vehicle'); // Optional if table name is `users`
    }
}