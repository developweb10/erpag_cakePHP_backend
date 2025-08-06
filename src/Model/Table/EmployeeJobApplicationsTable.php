<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class EmployeeJobApplicationsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('employee_job_applications'); // Optional if table name is `drivers`
    }
}