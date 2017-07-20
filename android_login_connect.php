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
		if (!$this->conn)
		{
			die(mysqli_connect_error());
		}
		return $this->conn;
	}

	public function VerifyUserAuthentication($email, $password) 
	{
		$sql = "SELECT * FROM users WHERE email='" . $email . "' AND password='" . $password ."'";
		echo $sql;
		$query = mysqli_query($this->conn, $sql);
		$user = [];
		while ($row = mysqli_fetch_array($query))
		{
			$user['user_id'] 	= $row['user_id'];
			$user['email']		= $row['email'];
			$user['name']		= $row['name'];

			return $user;
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