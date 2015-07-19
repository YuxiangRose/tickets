<?php

class TicketsController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	public function getIndex()
	{	
		$num =	$this->fileConvertAndUpdate();
		return View::make('ticket',array('num'=>$num));
		//$this->fileConvertAndUpdate();
	}

	public function fileConvertAndUpdate(){

		$dir = "../files/";
		$num = 0;
		$documents_group = scandir($dir);

		foreach ($documents_group as $key => $value) {
			if($value != "." && $value != ".."){
				$handler = new handler($dir,$value);
				//var_dump($handler);
				/*if($handler->getFileType() != NULL){
					try {
			            $document = Document::create(array(
			                'path' 			=> $handler->getPath(),
			                'fileName'  	=> $handler->getFileName(),
			                'fileType'  	=> $handler->getFileType(),
			                'systemName'    => $handler->getSystemName(),
			                'airlineName'   => $handler->getAirlineName(),
			                'ticketNumber'  => $handler->getTicketNumber(),
			                'dateString'    => $handler->getDateString(),
			                'orderOfDay'    => $handler->getOrderOfDay(),
			                'fileContent'   => $handler->getFileContent(),
			                'dateOfFile'    => $handler->getDateOfFile(),
			                'paxName'		=> $handler->getPaxName(),
			                'rloc'			=> $handler->getRloc(),
			                'ticketsType'	=> $handler->getTicketsType(),
			            ));
			            $document->save();
			            $num++;
			            rename($dir.$value, "../done/".$value);
			        } catch (Exception $e) {
			            $response['info'] = "fail";
			            $boolean = false;
			            echo $e;
			        }
		        }*/				
			}
		}

		//echo $num." files have been converted."; die;
		return $num;
	}

	public function update(){
		$data = array();
		$num = $this->fileConvertAndUpdate();
		$data['num'] = $num;
		echo json_encode($data);
	}


	/**
	 * function search()
	 * Using either ticketNumber, passengerName or rloc to search up the tickets
     */
	public function search(){
		$data = array();
		/* Check if ticket number entered is not null and is number */
		if(($_POST['ticketNumber'] != null) && (is_numeric($_POST['ticketNumber']))){
			$ticketNumber = trim($_POST['ticketNumber']);
		}else{
			$ticketNumber = "";
		}

		/* Parse the passenger name by space or slash */
		if (($_POST['passengerName'] != null) || empty(($_POST['passengerName']))) {
			$passengerName = strtoupper(trim($_POST['passengerName']));
			if(strpos($passengerName,'/')){
				$parsePassengerName = explode("/", $passengerName);
			}else{
				$parsePassengerName = explode(" ", $passengerName);
			}
			$first = (array_key_exists(0, $parsePassengerName) ? $parsePassengerName[0] : "");
			$mid   = (array_key_exists(1, $parsePassengerName) ? $parsePassengerName[1] : "");
			$last   = (array_key_exists(2, $parsePassengerName) ? $parsePassengerName[2] : "");
		}else{
			$passengerName = null;
		}

		/* Check if RLOC is entered */
		if (($_POST['rloc'] != null)) {
			$rloc = strtoupper(trim($_POST['rloc']));
		}else{
			$rloc = "";
		}

		$query = Document::query();
		if($ticketNumber != null){
			$query = $query->where('ticketNumber', 'LIKE', '%'.$ticketNumber.'%');
		}
		if($rloc != null){
			$query = $query->where('rloc', 'LIKE', '%'.$rloc.'%');
		}
		if($passengerName != null){
			$query = $query->where('paxName', 'LIKE', '%'.$first.'%')
						   ->where('paxName','LIKE','%'.$mid.'%')
						   ->where('paxName','LIKE','%'.$last.'%');
		}
		$model = $query->get();

		$index = 0;
		if(sizeof($model)>0){
			foreach ($model as $key => $value) {
				$document = $value->getAttributes();
				//if($document){
					$data[$index]['content']=$document['fileContent'];
					$data[$index]['dateOfFile']=$document['dateOfFile'];
					$data[$index]['paxName']=$document['paxName'];
					$data[$index]['airlineName']=$document['airlineName'];
					$data[$index]['systemName']=$document['systemName'];
					$data[$index]['ticketNumber']=$document['ticketNumber'];
				//}else{
					//$data['content'][]="Sorry the document does not exist, or hasn't been update yet, please click update and try again.";					
				//}
				$index++;
			}
			//$document = $model[0]->getAttributes();
			//$data['content'] = $document['fileContent']; 	
		}else{
			$data[$index]['content'] = "Sorry the document does not exist, or hasn't been update yet, please click update and try again.";
		}
		echo json_encode($data);
	}  //End search function

	/**
	 * function next()
	 * Searching the database to find the same systemName
	 * Check the max of all ticketNumber and then determine the next ticketNumber
	 * Passes content, ticketNumber and systemName to the view (ticket.blade.php)
	 */
	public function next(){
		$data = array();
		$systemName = $_POST['systemName'];
		$nextTicketNumber = $_POST['ticketNumber'] + 1;

		//used to get the nextTicketNumber if there is a sequential nextTicketNumber
		$model = Document::where('systemName', '=', $systemName)
							->where('ticketNumber', '=', ($nextTicketNumber))->get();

		if(sizeof($model) == 0){
			$data['content'] = 'Reached end of record';
			return json_encode($data);
		}
		//Getting all the same system number and stores the tickets in an array to find the max ticketNumber
		$getAllModel = Document::where('systemName', '=', $systemName)->get();

		$allTicketNumber = [];
		if(sizeof($getAllModel) > 0){
			foreach($getAllModel as $key => $value){
				$allTicketNumber[] = $value->ticketNumber;
			}
		}
		$maxTicketNumber = max($allTicketNumber);

		if($nextTicketNumber > $maxTicketNumber){
			$data['content'] = 'Reached end of record';
		}else{
			$data['content'] = $model[0]->fileContent;
			$data['ticketNumber'] = $model[0]->ticketNumber;
			$data['systemName'] = $model[0]->systemName;
		}

		echo json_encode($data);
	}
}