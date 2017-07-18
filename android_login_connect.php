<?php 

class Android_login_connect 
{
	private $conn;

	public function connect() 
	{

		$this->conn = mysqli_init(); 
		mysqli_ssl_set($this->conn, NULL, NULL, "PLNComm.pem", NULL, NULL); 
		mysqli_real_connect($this->conn, "pln-comm.mysql.database.azure.com", "azhary@pln-comm", "4kuGanteng", "db_pln_comm", 3306);
		
		//require_once "Android_login_config.php";

		//$this->conn = new Mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
		if (mysqli_connect_error())
		{
			die(mysqli_connect_error());
		}
		return $this->conn;
	}

	public function VerifyUserAuthentication($email, $password) 
	{
		if ($stmt = mysqli_prepare($this->conn, "SELECT user_id, email, password, name FROM users WHERE email = ?"))
		{
			mysqli_stmt_bind_param($stmt, "s", $email);
			
			if (mysqli_stmt_execute($stmt))
			{
				mysqli_stmt_bind_result($stmt, $token, $token2, $token3, $token4);
				mysqli_stmt_fetch($stmt);
				$user['user_id']	= $token;
				$user["email"] 		= $token2;
				$user["password"]	= $token3;
				$user["name"]		= $token4;
				mysqli_stmt_close($stmt);

				if ($password === $token3) return $user;
			}
		}

		return NULL;
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

?>