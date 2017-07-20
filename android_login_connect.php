<?php 

require_once "Android_login_config.php";

class Android_login_connect 
{
	private $conn;

	public function connect() 
	{

		$this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
		if (!$this->conn)
		{
			die(mysqli_connect_error());
		}
		return $this->conn;
	}

	public function VerifyUserAuthentication($email, $password) 
	{
		$sql = "SELECT * FROM users WHERE email='" . $email . "' AND password='" . $password ."'";
		$query = mysqli_query($this->conn, $sql);
		while ($row = mysqli_fetch_array($query))
		{
			$user['user_id']	= $row['USER_ID'];
			$user['email']		= $row['EMAIL'];
			$user['name']		= $row['NAME'];
			return $user;
		}

		return NULL;
	}
}

?>