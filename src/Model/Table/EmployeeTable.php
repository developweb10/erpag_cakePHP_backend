<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class EmployeeTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('employee'); // Optional if table name is `drivers`
    }
}