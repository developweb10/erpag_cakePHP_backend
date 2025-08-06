<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use DateTime;
use DateInterval;
use DatePeriod;


class ApiController extends Controller
{
	/* 
		https://book.cakephp.org/5/en/orm/query-builder.html
		Api created for get all drivers
		URL: https://api-prod-hrs.srul.co.uk/api/drivers
		Output: Return drivers information with Success message
	*/
   public function drivers(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		
		$this->autoRender = false;
		$data = $this->getTableLocator()->get('Employee')->find()->select(['id', 'first_name', 'email', 'avatar_id', 'join_date']);


		if(!empty($data)){
			$drivers=$data->all();
			$result=array('status'=>1,'message'=>'Drivers.','drivers'=>$drivers);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	/* 
		Api created for get all drivers
		URL: https://api-prod-hrs.srul.co.uk/api/driver/
		Output: Return drivers information with Success message
	*/
   public function driver($id){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		
		$this->autoRender = false;
		
		if(!empty($id)){
			$driversTable 	= $this->fetchTable('Employee');
			$resultsArray 	= $driversTable->find()->where(['id' => $id])->all()->first();
			
			if(!empty($resultsArray)){
				
				/*===Get driver shifts==*/
				
				$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
				$DriverShiftsTable 			= $this->fetchTable('DriverShifts');
				
				$DriverAssignedShifts 	= $DriverAssignedShiftsTable->find()->select(['shift_id'])->where(['driver_id' => $resultsArray->id])->all()->extract('shift_id')->toArray();
				
				if($DriverAssignedShifts):
					$driverShifts = $DriverShiftsTable->find()->where(['id IN' => $DriverAssignedShifts])->all();
				else:
					$driverShifts = array();
				endif;
				
				$resultsArray['assigned'] = $driverShifts;
				
				/*===Get driver Vehicle==*/
				
				$DriverAssignedVehicleTable 	= $this->fetchTable('DriverAssignedVehicle');
				$VehiclesTable 					= $this->fetchTable('Vehicles');
				
				$DriverAssignedVehicle 	= $DriverAssignedVehicleTable->find()->select(['vehicle_id'])->where(['driver_id' => $resultsArray->id])->all()->extract('vehicle_id')->toArray();
				
				if($DriverAssignedVehicle):
					$driverVehicles = $VehiclesTable->find()->where(['id IN' => $DriverAssignedVehicle])->all();
				else:
					$driverVehicles = array();
				endif;	
				
				$resultsArray['assignedVehicle'] = $driverVehicles;


				$result=array('status'=>1,'message'=>'Drivers.','driver_info'=>$resultsArray);

			}
			else{
				$result = array('status'=>0,'message'=>'No driver found with this id');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		
	}
	
	
	/*
		Api created for get all drivers schedule
		URL: https://api-prod-hrs.srul.co.uk/api/drivers_schedule
		Output: Return drivers information with Success message
	*/
   public function driversSchedule(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
			   
		$this->autoRender = false;
		
		$DriversTable 	= $this->fetchTable('Employee');
		
		if(isset($_REQUEST['start_date'])){
			$start_date = $_REQUEST['start_date'];
		}else{
			$start_date = date('Y-m-01');
		}
		
		if(isset($_REQUEST['end_date'])){
			$end_date = $_REQUEST['end_date'];
			$time 		= strtotime($end_date);
			$end_date 	= date("Y-m-d", strtotime("+1 day", $time));
		}else{
			$time 		= strtotime($start_date);
			$end_date 	= date("Y-m-d", strtotime("+1 month", $time));
			//$start_date = date('Y-m-t');
		}
		
		$drivers 	= $DriversTable->find()->select(['id', 'first_name', 'email', 'avatar_id', 'join_date'])->all();
		
		if(!empty($drivers)){

			$begin 	= new DateTime($start_date);
			$end 	= new DateTime($end_date);

			$interval = DateInterval::createFromDateString('1 day');
			$period = new DatePeriod($begin, $interval, $end);
			
			$driversInfo= array();
			$key		= 0;
			foreach($drivers as $driver):
				
				$driversInfo[$key] = $driver->toArray();
				foreach ($period as $dt):
					
					$currentdate = $dt->format("Y-m-d");
					
					/*===Get driver shifts==*/

					$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
					$DriverShiftsTable 			= $this->fetchTable('DriverShifts');
					
					$driverShifts = $DriverAssignedShiftsTable->find()
								->select([
									'assigned_shift_id'=> 'DriverAssignedShifts.id',
									'shift_id' => 'DriverShifts.id',
									'shift_name' => 'DriverShifts.shift_name',
									'start_time' => 'DriverShifts.start_time',
									'end_time' => 'DriverShifts.end_time',
									'shift_status' => 'DriverShifts.shift_status',
									'shift_type' => 'DriverShifts.shift_type',
								])
								->leftJoin(
									['DriverShifts' => 'driver_shifts'], // alias => table name
									['DriverShifts.id = DriverAssignedShifts.shift_id']
								)
								->where([
									'DriverAssignedShifts.driver_id' => $driver->id,
									'DriverAssignedShifts.shift_date' => $currentdate
								])
								->all();
													
					if($driverShifts):
					else:
						$driverShifts = null;
					endif;
					
					$driversInfo[$key]["schedule"][$currentdate]['assigned'] = $driverShifts;
					
					
					/*===Get driver availability==*/
					$currentdate = $dt->format("Y-m-d");

					$DriverAvailabilityTable 	= $this->fetchTable('DriverAvailability');
					
					$DriverAvailability			= $DriverAvailabilityTable->find()->where(['driver_id' => $driver->id, 'availability_date' => $currentdate])->all()->toArray();
					
					if($DriverAvailability):

					else:
						$DriverAvailability = null;
					endif;
					
					$driversInfo[$key]["schedule"][$currentdate]['availability'] = $DriverAvailability;
					
					/*===Get driver Vehicle==*/
					
					$DriverAssignedVehicleTable 	= $this->fetchTable('DriverAssignedVehicle');
					$VehiclesTable 					= $this->fetchTable('Vehicles');
					
					
					$driverVehicles = $DriverAssignedVehicleTable->find()
								->select([
									'assigned_vehicle_id'=> 'DriverAssignedVehicle.id',
									'vehicle_id' => 'Vehicles.id',
									'vehicle_name' => 'Vehicles.vehicle_name',
									'vehicle_number' => 'Vehicles.vehicle_number',
									'vehicle_type' => 'Vehicles.vehicle_type',
									'vehicle_rph' => 'Vehicles.vehicle_rph',
									'vehicle_nos' => 'Vehicles.vehicle_nos',
								])
								->leftJoin(
									['Vehicles' => 'vehicles'], // alias => table name
									['Vehicles.id = DriverAssignedVehicle.vehicle_id']
								)
								->where([
									'DriverAssignedVehicle.driver_id' => $driver->id,
									'DriverAssignedVehicle.assignd_date' => $currentdate
								])
								->all();
					
					if($driverVehicles):
					
					else:
						$driverVehicles = null;
					endif;	
					
					$driversInfo[$key]["schedule"][$currentdate]['assignedVehicle'] = $driverVehicles;
					
					
				endforeach;
				$key++;
			endforeach;
			
			$result=array('status'=>1,'message'=>'Drivers.','drivers'=>$driversInfo);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	
	/* 
		Api created for add new driver
		URL: https://api-prod-hrs.srul.co.uk/api/add_driver
		Output: Return user information with Success message
	*/

	public function addDriver(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$driversTable 	= $this->fetchTable('Employee');
			if(!empty($data) && !empty($data['driver_email'])){
				$check_email = $driversTable->find()
											->where(['email' => $data['driver_email']])
											->all()
											 ->first();
				if(empty($check_email)){
					
					$drivers 		= $driversTable->newEmptyEntity();

					$drivers->first_name 			= $data['driver_first_name'];
					$drivers->surname 				= $data['driver_surname'];
					$drivers->middle_name 			= $data['driver_middle_name'];
					$drivers->gender 				= $data['driver_gender'];
					$drivers->know_as 				= $data['driver_known_as'];
					$drivers->smoker 				= $data['driver_smoker'];
					$drivers->address_1 			= $data['driver_address'];
					$drivers->address_2 			= $data['driver_address_alt'];
					$drivers->city 					= $data['driver_city'];
					$drivers->town 					= $data['driver_town'];
					$drivers->country 				= $data['driver_country'];
					$drivers->post_code 			= $data['driver_postcode'];
					$drivers->mobile_number 		= $data['driver_mobile_number'];
					$drivers->house_number 			= $data['driver_house_number'];
					$drivers->date_of_birth 		= $data['driver_dob'];
					$drivers->email 				= $data['driver_email'];
					$drivers->personal_email 		= $data['driver_personal_email'];
					$drivers->ec_first_name 		= $data['driver_emg_cont_first_name'];
					$drivers->ec_last_name 			= $data['driver_emg_cont_fsecond_name'];
					$drivers->ec_home_telephone 	= $data['driver_emg_cont_home_tel'];
					$drivers->ec_work_telephone 	= $data['driver_emg_cont_mobile'];
					$drivers->ec_relationship 		= $data['driver_emg_cont_relationship'];
					$drivers->hours_per_week 		= $data['driver_hours_per_week'];
					$drivers->hourly_rate 			= $data['driver_hourly_rate'];
					$drivers->join_date 			= date('Y-m-d h:i:s');


					if ($driversTable->save($drivers)) {
						// The $article entity contains the id now
						$id = $drivers->id;

						//send mail for account verification
						//$this->_sendVerificationEmail($id,$data['email']);
						
						$resultsArray = $driversTable->find()
											->where(['id' => $id])
											->all()
											 ->first();
											
						$result=array('status'=>1,'message'=>'Successfully registered.','driver_info'=>$resultsArray);
					}
					else{
						$result=array('status'=>0,'message'=>'Something went wrong,try again!');
					}
				}else{
					$result = array('status'=>0,'message'=>'The email you have entered already exist!');
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
		 /*---- comment the die and enable below line if want to check or debug this in browser ------*/
		 //$this->set('user', $user);
	}
	
	/* 
		Api created for add new driver
		URL: https://api-prod-hrs.srul.co.uk/api/add_driver_availability
		Output: Return user information with Success message
	*/

	public function addDriverAvailability(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$driversTable 	= $this->fetchTable('DriverAvailability');
			if(!empty($data) && !empty($data['availability_date'])){
				$check_email = $driversTable->find()
											->where(['availability_date' => $data['availability_date']])
											->all()
											 ->first();
				if(empty($check_email)){
					
					$drivers 		= $driversTable->newEmptyEntity();

					$drivers->driver_id 				= $data['driver_id'];
					$drivers->availability_status 		= $data['availability_status'];
					$drivers->availability_name 		= $data['availability_name'];
					$drivers->availability_start_time 	= $data['availability_start_time'];
					$drivers->availability_end_time 	= $data['availability_end_time'];
					$drivers->availability_date 		= $data['availability_date'];


					if ($driversTable->save($drivers)) {
						// The $article entity contains the id now
						$id = $drivers->id;

						//send mail for account verification
						//$this->_sendVerificationEmail($id,$data['email']);
						
						$resultsArray = $driversTable->find()
											->where(['id' => $id])
											->all()
											 ->first();
											
						$result=array('status'=>1,'message'=>'Successfully added.','availability_info'=>$resultsArray);
					}
					else{
						$result=array('status'=>0,'message'=>'Something went wrong,try again!');
					}
				}else{
					$query = $driversTable->updateQuery();
					$query->set(['availability_status' => $data['availability_status'], 'availability_name' => $data['availability_name'], 'availability_start_time' => $data['availability_start_time'], 'availability_end_time' => $data['availability_end_time']])
						->where(['id' => $check_email->id])
						->execute();
						
					$resultsArray = $driversTable->find()
											->where(['id' => $check_email->id])
											->all()
											 ->first();
						
					$result=array('status'=>1,'message'=>'Successfully added.','availability_info'=>$resultsArray);
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
	}
	
	
	/* 
		Api created for add new driver shift
		URL: https://api-prod-hrs.srul.co.uk/api/add_driver_shift
		Output: Return user information with Success message
	*/

	public function addDriverShift(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$driversTable 	= $this->fetchTable('DriverShifts');
			if(!empty($data) && !empty($data['start_time'])){
					
				$driver_shift 		= $driversTable->newEmptyEntity();

				$driver_shift->shift_name 	= $data['shift_name'];
				$driver_shift->start_time 	= $data['start_time'];
				$driver_shift->end_time 	= $data['end_time'];
				$driver_shift->shift_status = $data['shift_status'];
				$driver_shift->shift_type 	= $data['shift_type'];


				if ($driversTable->save($driver_shift)) {
					// The $article entity contains the id now
					$id = $driver_shift->id;

					//send mail for account verification
					//$this->_sendVerificationEmail($id,$data['email']);
					
					$resultsArray = $driversTable->find()
										->where(['id' => $id])
										->all()
										 ->first();
										
					$result=array('status'=>1,'message'=>'Successfully added.','shift_info'=>$resultsArray);
				}
				else{
					$result=array('status'=>0,'message'=>'Something went wrong,try again!');
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
	}
	
	/* 
		Api created for get all shifts
		URL: https://api-prod-hrs.srul.co.uk/api/get_shifts
		Output: Return vehicles information with Success message
	*/
   public function getShifts(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
			   
		$this->autoRender = false;
		$data = $this->getTableLocator()->get('DriverShifts')->find()->where(['is_active' => 1]);


		if(!empty($data)){
			$DriverShifts=$data->all();
			$result=array('status'=>1,'message'=>'Driver Shifts.','driver_shifts'=>$DriverShifts);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	/* 
		Api created for delete driver shift
		URL: https://api-prod-hrs.srul.co.uk/api/delete_driver_shift
		Output: Return user information with Success message
	*/

	public function deleteDriverShift($id){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		
		$data=$this->request->getData();
		$DriverShiftsTable 	= $this->fetchTable('DriverShifts');
		if($id){
			$check_data = $DriverShiftsTable->find()
										->where(['id' => $id])
										->all()
										 ->first();
			if(!empty($check_data)){
				
				$query = $DriverShiftsTable->updateQuery();
				$query->set(['is_active' => 0])
						->where(['id' => $check_data->id])
						->execute();
				$result=array('status'=>1,'message'=>'Shift deleted successfully.');
			}else{
				$result = array('status'=>0,'message'=>'Data not found with this id');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		
		echo json_encode($result); die;
		 
	}
	
	/* 
		Api created for Assigned shift to driver 
		URL: https://api-prod-hrs.srul.co.uk/api/assigne_driver_shift
		Output: Return user information with Success message
	*/

	public function assigneDriverShift(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
			if(!empty($data) && !empty($data['shift_id']) && !empty($data['driver_id'])){
					
				$DriverAssignedShifts 		= $DriverAssignedShiftsTable->newEmptyEntity();

				$DriverAssignedShifts->shift_id 	= $data['shift_id'];
				$DriverAssignedShifts->driver_id 	= $data['driver_id'];
				$DriverAssignedShifts->shift_date 	= $data['shift_date'];

				if ($DriverAssignedShiftsTable->save($DriverAssignedShifts)) {
					// The $article entity contains the id now
					$id = $DriverAssignedShifts->id;
										
					$result=array('status'=>1,'message'=>'Shift assigned/updated successfully','assigned_shift_id'=> $id);
				}
				else{
					$result=array('status'=>0,'message'=>'Something went wrong,try again!');
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
	}
	
	
	/* 
		Api created for remove driver shift
		URL: https://api-prod-hrs.srul.co.uk/api/removed_driver_shift
		Output: Return user information with Success message
	*/

	public function removedDriverShift($id){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
		
		if($id){
			// $check_data = $DriverAssignedShiftsTable->find()
									// ->where(['id' => $id])
									// ->all()
									 // ->first();
			// if(!empty($check_data)){

				$query = $DriverAssignedShiftsTable->deleteQuery();
				$query->where(['id' => $id])->execute();
				//echo "hkhj";
				$result= array('status'=>1,'message'=>'Shift removed successfully');
			// }else{
				// $result = array('status'=>0,'message'=>'Data not found with this id 12');
			// }
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		 
	}
	
	/* 
		Api created for driver shift confirmed by manager
		URL: https://api-prod-hrs.srul.co.uk/api/manager_confirmed_driver_shift
		Output: Return user information with Success message
	*/

	public function managerConfirmedDriverShift($id){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
		$DriverShiftsTable 			= $this->fetchTable('DriverShifts');
		
		if($id){
			$check_data = $DriverAssignedShiftsTable->find()
									->where(['id' => $id])
									->all()
									 ->first();
			if(!empty($check_data)){
				
				$query = $DriverAssignedShiftsTable->updateQuery();
				$query->set(['is_confirmed_by_manager' => 'true'])
						->where(['id' => $check_data->id])
						->execute();
				
				$resultsArray = $DriverAssignedShiftsTable->find()
										->where(['id' => $id])
										->all()
										 ->first();
				
				$resultsArray['shift_detail'] = $DriverShiftsTable->find()
										->where(['id' => $resultsArray->shift_id])
										->all()
										 ->first();
				
				$result=array('status'=>1,'message'=>'Shift confirmed by manager', 'updatedShift'=>$resultsArray);
			}else{
				$result = array('status'=>0,'message'=>'Data not found with this id');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		 
	}
	
	/* 
		Api created for availability confirmed by driver
		URL: https://api-prod-hrs.srul.co.uk/api/driver_confirmed_availability
		Output: Return user information with Success message
	*/

	public function driverConfirmedAvailability($id){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		$DriverAvailabilityTable 	= $this->fetchTable('DriverAvailability');
		
		if($id){
			$check_data = $DriverAvailabilityTable->find()
									->where(['id' => $id])
									->all()
									 ->first();
			if(!empty($check_data)){
				
				$query = $DriverAvailabilityTable->updateQuery();
				$query->set(['is_driver_confirmed' => 'true', 'availability_status' => 'Available', 'availability_name' => 'Available'])
						->where(['id' => $check_data->id])
						->execute();
				
				$resultsArray = $DriverAvailabilityTable->find()
										->where(['id' => $id])
										->all()
										 ->first();
				
				$result=array('status'=>1,'message'=>'Driver availability confirmed', 'updatedShift'=>$resultsArray);
			}else{
				$result = array('status'=>0,'message'=>'Data not found with this id');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		 
	}
	
	/* 
		Api created for get all vehicles
		URL: https://api-prod-hrs.srul.co.uk/api/vehicles_list
		Output: Return vehicles information with Success message
	*/
   public function vehiclesList(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		
		$this->autoRender = false;
		$data = $this->getTableLocator()->get('Vehicles')->find();


		if(!empty($data)){
			$vehicles=$data->all();
			$result=array('status'=>1,'message'=>'Vehicles.','vehicles'=>$vehicles);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	/* 
		Api created for add new vehicle
		URL: https://api-prod-hrs.srul.co.uk/api/add_vehicle
		Output: Return user information with Success message
	*/

	public function addVehicle(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		//if($this->request->is('post')){
			$data=$_REQUEST;
			$vehiclesTable 	= $this->fetchTable('Vehicles');
			if(!empty($data) && !empty($data['vehicle_number'])){
				$check_email = $vehiclesTable->find()
											->where(['vehicle_number' => $data['vehicle_number']])
											->all()
											 ->first();
				if(empty($check_email)){
					
					$vehicle 		= $vehiclesTable->newEmptyEntity();

					$vehicle->vehicle_name 			= $data['vehicle_name'];
					$vehicle->vehicle_number 		= $data['vehicle_number'];
					$vehicle->vehicle_type 			= $data['vehicle_type'];
					if(isset($data['vehicle_rph'])){
						$vehicle->vehicle_rph 			= $data['vehicle_rph'];
					}else{
						$vehicle->vehicle_rph 			= 120;
					}

					if(isset($data['vehicle_nos'])){
						$vehicle->vehicle_nos 			= $data['vehicle_nos'];
					}else{
						$vehicle->vehicle_nos 			= 5;
					}



					if ($vehiclesTable->save($vehicle)) {
						// The $article entity contains the id now
						$id = $vehicle->id;

						//send mail for account verification
						//$this->_sendVerificationEmail($id,$data['email']);
						
						$resultsArray = $vehiclesTable->find()
											->where(['id' => $id])
											->all()
											 ->first();
											
						$result=array('status'=>1,'message'=>'Successfully Added.','vehicle_info'=>$resultsArray);
					}
					else{
						$result=array('status'=>0,'message'=>'Something went wrong,try again!');
					}
				}else{
					$result = array('status'=>0,'message'=>'Vehicle is already added with this number');
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		// }
		// else{
			// $result = array('status'=>0,'message'=>'Method Mismatch!');
		// }
		 echo json_encode($result); die;
		 
		 /*---- comment the die and enable below line if want to check or debug this in browser ------*/
		 //$this->set('user', $user);
	}
	
	
	/* 
		Api created for delete vehicle
		URL: https://api-prod-hrs.srul.co.uk/api/delete_vehicle
		Output: Return user information with Success message
	*/

	public function deleteVehicle(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		$data=$_REQUEST;
		$vehiclesTable 	= $this->fetchTable('Vehicles');
		if(!empty($data) && !empty($data['vehicle_number'])){
			$check_email = $vehiclesTable->find()
										->where(['vehicle_number' => $data['vehicle_number']])
										->all()
										 ->first();
			if(!empty($check_email)){
				
				$DriverAssignedVehicleTable 	= $this->fetchTable('DriverAssignedVehicle');
				
				$check_data = $DriverAssignedVehicleTable->find()
									->where(['vehicle_id' => $check_email->id])
									->all()
									 ->first();
				if(empty($check_data)){
				
					$query = $vehiclesTable->deleteQuery();
					$query->where(['id' => $check_email->id])->execute();
					$result=array('status'=>1,'message'=>'Successfully Deleted.');
				}
				else{
					$result=array('status'=>0,'message'=>'The vehicle is assigned to the Drivers. You cannot delete this vehicle.');
				}
			}else{
				$result = array('status'=>0,'message'=>'No vehicle found releted to this number');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		 
	}
	
	
	/* 
		Api created for Assigned shift to driver 
		URL: https://api-prod-hrs.srul.co.uk/api/assign_driver_vehicle
		Output: Return user information with Success message
	*/

	public function assignDriverVehicle(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$DriverAssignedVehicleTable 	= $this->fetchTable('DriverAssignedVehicle');
			$VehiclesTable 					= $this->fetchTable('Vehicles');
			
			if(!empty($data) && !empty($data['vehicle_id']) && !empty($data['driver_id'])){
					
				$DriverAssignedVehicle 		= $DriverAssignedVehicleTable->newEmptyEntity();

				$DriverAssignedVehicle->vehicle_id 		= $data['vehicle_id'];
				$DriverAssignedVehicle->driver_id 		= $data['driver_id'];
				$DriverAssignedVehicle->assignd_date 	= $data['assignd_date'];

				if ($DriverAssignedVehicleTable->save($DriverAssignedVehicle)) {
					// The $article entity contains the id now
					$id = $DriverAssignedVehicle->id;
					
					$vehicle_detail = $VehiclesTable->find()
										->where(['id' => $data['vehicle_id']])
										->all()
										 ->first();
										
					$result=array('status'=>1,'message'=>'Vehicle assigned successfully','assigned_vehicle_id'=> $id,'vehicle_detail'=> $vehicle_detail);
				}
				else{
					$result=array('status'=>0,'message'=>'Something went wrong,try again!');
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
	}
	
	
	/* 
		Api created for remove driver shift
		URL: https://api-prod-hrs.srul.co.uk/api/removed_driver_vehicle
		Output: Return user information with Success message
	*/

	public function removedDriverVehicle($id){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		$DriverAssignedVehicleTable 	= $this->fetchTable('DriverAssignedVehicle');
		
		if($id){
			// $check_data = $DriverAssignedVehicleTable->find()
									// ->where(['id' => $id])
									// ->all()
									 // ->first();
			// if(!empty($check_data)){

				$query = $DriverAssignedVehicleTable->deleteQuery();
				$query->where(['id' => $id])->execute();
				$result=array('status'=>1,'message'=>'Vehicle removed successfully');
			// }else{
				// $result = array('status'=>0,'message'=>'Data not found with this id');
			// }
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		 
	}
	
	
	/* 
		Api created for send notifications
		URL: https://api-prod-hrs.srul.co.uk/api/send_notification
		Output: Return user information with Success message
	*/

	public function sendNotification(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$NotificationsTable 	= $this->fetchTable('NotificationsList');
			if(!empty($data) && !empty($data['notification_text'])){
				
				$driversTable 	= $this->fetchTable('Employee');
					
				
				
				if($data['sendToTarget']=='allActiveWorkforce'){
					$drivers 	= $driversTable->find()->select(['id'])->all()->extract('id')->toArray();
				}
				
				if($data['sendToTarget']=='managersConfirmedToday'){
					$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
					
					$drivers 	= $DriverAssignedShiftsTable->find()->select(['driver_id'])->where(['shift_date' => date('Y-m-d'),'is_confirmed_by_manager'=> 'true'])->all()->extract('driver_id')->toArray();

				}
				
				if($data['sendToTarget']=='unconfirmedToday'){
					$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
					
					$drivers 	= $DriverAssignedShiftsTable->find()->select(['driver_id'])->where(['shift_date' => date('Y-m-d'),'is_confirmed_by_manager'=> 'false'])->all()->extract('driver_id')->toArray();

				}
				
				if($data['sendToTarget']=='unconfirmedToday'){
					$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
					
					$drivers 	= $DriverAssignedShiftsTable->find()->select(['driver_id'])->where(['shift_date' => date('Y-m-d'),'is_confirmed_by_manager'=> 'false'])->all()->extract('driver_id')->toArray();

				}
				if($data['sendToTarget']=='unconfirmedTomorrow'){
					$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
					$end_date	= date('Y-m-d');
					$time 		= strtotime($end_date);
					$end_date 	= date("Y-m-d", strtotime("+1 day", $time));
					
					$drivers 	= $DriverAssignedShiftsTable->find()->select(['driver_id'])->where(['shift_date' =>$end_date,'is_confirmed_by_manager'=> 'false'])->all()->extract('driver_id')->toArray();

				}
				
				if($data['sendToTarget']=='managersAssignedNextDay'){
					$DriverAssignedShiftsTable 	= $this->fetchTable('DriverAssignedShifts');
					$end_date	= date('Y-m-d');
					$time 		= strtotime($end_date);
					$end_date 	= date("Y-m-d", strtotime("+1 day", $time));
					
					$drivers 	= $DriverAssignedShiftsTable->find()->select(['driver_id'])->where(['shift_date' =>$end_date])->all()->extract('driver_id')->toArray();

				}
				
				if (str_contains($data['sendToTarget'], 'individual')) {
					$d = explode('-', $data['sendToTarget']);
					$drivers 	= array($d[1]);

				}
				if(!empty($drivers)):
					foreach($drivers as $driver):
						
						$driversTable 	= $this->fetchTable('Employee');
						$driver_name 	= $driversTable->find()->select(['first_name'])->where(['id' => $driver])->all()->extract('first_name')->first();

						$notification 		= $NotificationsTable->newEmptyEntity();

						$notification->notification_type 			= $data['notification_type'];
						$notification->notification_text 			= $data['notification_text'];
						$notification->delivery_status 				= 'Sent';
						$notification->delivered_by_id 				= $data['delivered_by_id'];
						$notification->send_to_target_display 		= $data['sendToTarget'];
						$notification->driver_id 					= $driver;
						$notification->driver_name 					= $driver_name;
						$notification->sending_time 				= date('Y-m-d H:i:s');
					
						$NotificationsTable->save($notification);
					endforeach;
				endif;	
										
				$result=array('status'=>1,'message'=>'Notification Send Successfully.');
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
		 /*---- comment the die and enable below line if want to check or debug this in browser ------*/
		 //$this->set('user', $user);
	}
	
	/*
		Api created for get all notifications
		URL: https://api-prod-hrs.srul.co.uk/api/notifications
		Output: Return drivers information with Success message
	*/
   public function notifications(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
			   
		$this->autoRender = false;
		
		$NotificationsTable 	= $this->fetchTable('NotificationsList');
		
		$start_date="";
		
		if(isset($_REQUEST['start_date'])){
			$start_date = $_REQUEST['start_date'];
		}
		
		if(isset($_REQUEST['end_date'])){
			$end_date = $_REQUEST['end_date'];
			$time 		= strtotime($end_date);
			$end_date 	= date("Y-m-d", strtotime("+1 day", $time));
			
			if(isset($_REQUEST['start_date'])){
				$start_date = date("Y-m-d", strtotime("-7 day", $time));
			}
		}else{
			$end_date 	= date("Y-m-d");
			$time 		= strtotime($end_date);
			$end_date 	= date("Y-m-d", strtotime("+1 day", $time));
		}
		
		if(!empty($start_date)){
			$where['sending_time >']= $start_date;
		}
		
		if(!empty($end_date)){
			$where['sending_time <=']= $end_date;
		}
		if(isset($_REQUEST['workforceId'])){
			$where['driver_id']= $_REQUEST['workforceId'];
		}
		
		$notifications 	= $NotificationsTable->find()->where($where)->all()->toArray();
		
		if(!empty($notifications)){
			$result=array('status'=>1,'message'=>'Notifications List.','notifications'=>$notifications);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	
	/* 
		Api created for add employee job applications data
		URL: https://api-prod-hrs.srul.co.uk/api/add_job_applications
		Output: Return user information with Success message
	*/

	public function addJobApplications(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		if($this->request->is('post')){
			$data=$this->request->getData();
			$EmployeeJobTable 	= $this->fetchTable('EmployeeJobApplications');
			if(!empty($data) && !empty($data['email'])){
				$check_email = $EmployeeJobTable->find()
											->where(['email' => $data['email']])
											->all()
											 ->first();
				if(empty($check_email)){
					
					$employee 		= $EmployeeJobTable->newEmptyEntity();

					$employee->first_name 			= $data['first_name'];
					$employee->surname 				= $data['surname'];
					$employee->gender 				= $data['gender'];
					$employee->address_1 			= $data['address'];
					$employee->address_2 			= $data['address_alt'];
					$employee->city 					= $data['city'];
					$employee->town 					= $data['town'];
					$employee->country 				= $data['country'];
					$employee->post_code 			= $data['postcode'];
					$employee->mobile_number 		= $data['mobile_number'];
					$employee->date_of_birth 		= $data['dob'];
					$employee->email 				= $data['email'];
					$employee->personal_email 		= $data['email'];
					$employee->ec_first_name 		= $data['emg_cont_first_name'];
					$employee->ec_work_telephone 	= $data['emg_cont_mobile'];
					$employee->nationality 			= $data['nationality'];
					$employee->join_date 			= date('Y-m-d h:i:s');
					$employee->uk_work_right			= $data['uk_work_right'];
					$employee->uk_driving_licence	= $data['uk_driving_licence'];
					$employee->proof_of_address		= $data['proof_of_address'];
					$employee->license_penalty_points= $data['license_penalty_points'];
					$employee->ability_to_lift_parcel= $data['ability_to_lift_parcel'];


					if ($EmployeeJobTable->save($employee)) {
						// The $article entity contains the id now
						$id = $employee->id;

						//send mail for account verification
						//$this->_sendVerificationEmail($id,$data['email']);
						
						$resultsArray = $EmployeeJobTable->find()
											->where(['id' => $id])
											->all()
											 ->first();
											
						$result=array('status'=>1,'message'=>'Successfully registered.','employee_info'=>$resultsArray);
					}
					else{
						$result=array('status'=>0,'message'=>'Something went wrong,try again!');
					}
				}else{
					$result = array('status'=>0,'message'=>'The email you have entered already exist!');
				}
			}
			else{
				$result = array('status'=>0,'message'=>'Data is Empty!');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Method Mismatch!');
		}
		 echo json_encode($result); die;
		 
		 /*---- comment the die and enable below line if want to check or debug this in browser ------*/
		 //$this->set('user', $user);
	}
	
	/* 
		Api created for get all candidates
		URL: https://api-prod-hrs.srul.co.uk/api/candidates
		Output: Return canditates information with Success message
	*/
   public function candidates(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		
		$this->autoRender = false;
		$data = $this->getTableLocator()->get('EmployeeJobApplications')->find();


		if(!empty($data)){
			$candidates=$data->all();
			$candidate_list = array();
			foreach($candidates as $candidate):
				$data = $this->getTableLocator()->get('Employee')->find()->select(['id', 'first_name', 'email', 'avatar_id', 'join_date'])->where(['email' => $candidate->email])
									->all()
									 ->first();
				if(empty($check_data)){
					$candidate_list[] = $candidate;
				}
			endforeach;
			$result=array('status'=>1,'message'=>'Canditates.','canditates'=>$candidate_list);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	/* 
		Api created for get all employees
		URL: https://api-prod-hrs.srul.co.uk/api/employees
		Output: Return canditates information with Success message
	*/
   public function employees(){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
		
		$this->autoRender = false;
		$data = $this->getTableLocator()->get('Employee')->find();


		if(!empty($data)){
			$candidates=$data->all();
			$result=array('status'=>1,'message'=>'Employees.','employees'=>$candidates);

		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}

		echo json_encode($result); die;
		
	}
	
	
	/* 
		Api created for update candidate status
		URL: https://api-prod-hrs.srul.co.uk/api/update_candidate_status
		Output: Return user information with Success message
	*/

	public function updateCandidateStatus($id, $status){
	   
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization');
				
		$EmployeeJobApplications 	= $this->fetchTable('EmployeeJobApplications');
		
		if($id){
			$check_data = $EmployeeJobApplications->find()
									->where(['id' => $id])
									->all()
									 ->first();
			if(!empty($check_data)){
				
				$query = $EmployeeJobApplications->updateQuery();
				$query->set(['candidate_status' => $status])
						->where(['id' => $check_data->id])
						->execute();
				
				$resultsArray = $EmployeeJobApplications->find()
										->where(['id' => $id])
										->all()
										 ->first();
				
				$result=array('status'=>1,'message'=>'Candidate status Updated', 'updatedata'=>$resultsArray);
			}else{
				$result = array('status'=>0,'message'=>'Data not found with this id');
			}
		}
		else{
			$result = array('status'=>0,'message'=>'Data is Empty!');
		}
		echo json_encode($result); die;
		 
	}
	
}
