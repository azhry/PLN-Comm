<?php 
require_once "android_login_connect.php";
$db = new Android_login_connect();
$db->connect();

$response["error"] = FALSE;

if (isset($_POST['email'], $_POST['password']))
{
	$email 		= $_POST['email'];
	$password 	= md5($_POST['password']);

	$user = $db->VerifyUserAuthentication($email, $password);
	if ($user != NULL)
	{
		$response["user"]["user_id"] 	= $user["user_id"];
		$response["user"]["email"] 		= $user["email"];
	}
	else
	{
		$response["error"] 		= TRUE;
		$response["error_msg"] 	= "Login credentials are wrong. Please try again!";
	}
}
else
{
	$response["error"] 		= TRUE;
	$response["error_msg"] 	= "Required parameters email or password is missing!";
}

echo json_encode($response);

// Output
// {"error":false,"user":{"user_id": 1234, "email": "arliansyah_azhary@yahoo.com"}}