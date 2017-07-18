<?php 

class Android_login_connect 
{
	private $conn;

	public function connect() 
	{
		require_once "android_login_config.php";

		$this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
		//var_dump($this->conn);
		return $this->conn;
	}

	public function VerifyUserAuthentication($email, $password) 
	{
		$stmt = $this->conn->prepare("SELECT user_id, email, password, name FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		
		if ($stmt->execute())
		{
			$stmt->bind_result($token, $token2, $token3, $token4);
			while ($stmt->fetch())
			{
				$user['user_id']	= $token;
				$user["email"] 		= $token2;
				$user["password"]	= $token3;
				$user["name"]		= $token4;
			}
			$stmt->close();

			if ($password === $token3) return $user;
		}
		else return NULL;
	}

	public function CheckExistingUser($email)
	{
		$stmt = $this->conn->prepare("SELECT email FROM users WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows > 0)
		{
			$stmt->close();
			return TRUE;
		}
		else
		{
			$stmt->close();
			return FALSE;
		}
	}
}