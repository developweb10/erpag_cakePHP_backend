<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class DriversTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('drivers'); // Optional if table name is `drivers`
    }
}