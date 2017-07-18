<?php 

public function VerifyUserAuthentication($email, $password) {
	$stmt = $this->conn->prepare("SELECT email, password FROM user WHERE email = ?");
	$stmt->bind_param("s", $email);
	
}