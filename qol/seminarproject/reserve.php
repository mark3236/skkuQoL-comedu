<?php
require($_SERVER["DOCUMENT_ROOT"].'/../dbconfig.php');
/*
dbconfig.php file outside of webroot has the following variables setup globally

$dbservername;
$dbusername;
$dbpassword;
$dbname;

*/

//note : $_POST indexes using "name" attributes from the form.
$purpose = $_POST['purpose'];
$day = $_POST['day'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$groupsize = $_POST['groupsize'];
$reservename = $_POST['reservename'];
$password = $_POST['password'];

//set response header
header('Content-type:application/json;charset=utf-8');
$response='NULL';

//input sanitization & selection verification ( sanitization needs work )
if(!(($purpose>=0)&&($purpose<=4))){
    $response = "ERROR : 사용목적을 선택해주십시오.";
    echo json_encode(["response" => $response]);
    die();
}
$date_regex ="/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
if(!preg_match($date_regex, $day)){
    $response = "ERROR : 날짜를 선택해주십시오.";
    echo json_encode(["response" => $response]);
    die();
}
else if(!(($start_time>=10)&&($start_time<=22))){
   $response = "ERROR : 시작시간을 선택해주세요.";
   echo json_encode(["response" => $response]);
   die();
}
else if(!(($end_time>=10)&&($end_time<=22))){
    $response = "ERROR : 종료시간을 선택해주세요.";
    echo json_encode(["response" => $response]);
    die();
}
else if(strlen($groupsize)>2){
    $response = "ERROR : 사용인원 수를 선택해주세요.";
    echo json_encode(["response" => $response]);
    die();
}
else if(strlen($reservename)>13 || strlen($reservename)<2){
    $response = "ERROR : 이름은 2자 이상 10자 이내로 입력해주세요.";
    echo json_encode(["response" => $response]);
    die();
}
else if(strlen($password)>15 || strlen($password)<3){
    $response = "ERROR : 비밀번호는 3자 이상 10자 이내로 입력해주세요.";
    echo json_encode(["response" => $response]);
    die();
}


//recaptcha
if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])){
			//your site secret key
			$secret = '6LfOBR8UAAAAAKnuenB-aiDeUIBajjuAGiVel5li';
    	    //get verify response data
    	    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
			$responseData = json_decode($verifyResponse);
			if(!($responseData->success)){
				$response="인증에 실패하였습니다.";
                echo json_encode(["response" => $response]);
                die();
			}
		}
else{
	$response="인증 후 시도해주세요!";
    echo json_encode(["response" => $response]);
    die();
}
//recaptcha passed without issues



// Create connection
$conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//set characterset that php thinks our database is using
if (!$conn->set_charset("utf8")) {
	die("utf8 문자 세트를 가져오다가 에러가 났습니다 :".$conn->error." 현재 문자 세트 : ".$conn->character_set_name());
}


//find if there's already a reservation by purpose!=0(not personal use) and reservedate matches and time conflicts
$sql = "SELECT * FROM admin.qol_seminarreservelist WHERE (reservedate = '$day' and purpose>0) and ((starttime<=$end_time) and (endtime>=$start_time));";

//run mysql query
$result = $conn->query($sql);


//if purpose is not personal yet the query finding non-personal(therefore official) use returned something, there's a conflict.
if ($result->num_rows > 0 && $purpose!=0) {
    $response = "ERROR : CANNOT RESERVE AT SPECIFIED TIME";
}
else {
    $sql = "INSERT INTO admin.qol_seminarreservelist(purpose, studentname, reservedate, starttime, endtime, groupsize, password) VALUES('$purpose', '$reservename', '$day', $start_time, $end_time, '$groupsize', '$password')";
    //insertion query
    $result = $conn->query($sql);
    if($result === TRUE){
        $response = "예약 성공!";
    }
    else{
        $response = "ERROR : Something went wrong when inserting into database! $purpose,$reservename,$day,$start_time,$end_time,$groupsize,$password $conn->error";
    }
}

echo json_encode(["response" => $response]);

//close connection
$conn->close();
?>