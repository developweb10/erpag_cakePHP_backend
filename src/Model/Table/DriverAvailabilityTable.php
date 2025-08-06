<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class DriverAvailabilityTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('driver_availability'); // Optional if table name is `users`
    }
}