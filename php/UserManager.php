<?php

class UserManager
{

	private static $USER_EXT = "usr";
	private static $SALT_N_ROUNDS = '$6$rounds=5000$';
	
	private $userFolder = "users/";
	private $currentUserFiles;

	public function __construct() {
		$this->currentUserFiles = array();
		
		$userFiles = scandir($this->userFolder);
		
		foreach ($userFiles as $userFile)
		{
			if (is_dir($userFile))
				continue;
			
			$fileInfo = pathinfo($userFile);
			$userID = $fileInfo["filename"];
			$userFileName = $fileInfo["basename"];
			$this->currentUserFiles[$userID] = $this->userFolder . $userFileName;
			
		}
	}
	
	public function doesUserExist($userName)
	{	return array_key_exists($userName, $this->currentUserFiles);	}
	
	public function createUser($userName, $password)
	{
		if ($this->doesUserExist($userName))
			throw new Exception("User already exists");
		
		$usrHandle = fopen($this->userFolder . $userName . "." . self::$USER_EXT, "w");
		$salt = $this->generateSalt(16);
		$secureHash = $this->generateSecureHash($password, $salt);
		// Username:SALT:hash:Wins:Loses
		fwrite($usrHandle, $userName . ":" . $salt . ":" . $secureHash . ":0:0\n");
		fclose($usrHandle);		
	}
	
	public function getAllUsers()
	{	return array_keys($this->currentUserFiles);	}
	
	public function authenticateUser($username, $password)
	{
		if (! $this->doesUserExist($username))
			return false;
		
		return ($this->generateSecureHash($password, $this->getSalt($username)) == $this->getSecureHash($username));
		
	}
	
	/* Positions:
	 * 0 = username
	 * 1 = salt
	 * 2 = hash
	 * 3 = wins
	 * 4 = loses
	*/	
	private function getUserElement($userName, $position)
	{
		$fileContents = file($this->getUserFileName($userName));
		$userContents = trim($fileContents[0]);
		$elements = explode(":", $userContents);
		
		return $elements[$position];
	}
			
	private function getSecureHash($userName)
	{	return $this->getUserElement($userName, 2);	}
	
	private function getSalt($userName)
	{	return $this->getUserElement($userName, 1);	}
	
	public function getUserWins($userName)
	{	return $this->getUserElement($userName, 3);	}
	
	public function getUserLoses($userName)
	{	return $this->getUserElement($userName, 4); }
	
	public function addUserWin($userName)
	{
		$fileContents = file($this->getUserFileName($userName));
		$userContents = explode(":", trim($fileContents[0]));
		$wins = intval($userContents[3]);
		$userContents[3] = strval($wins + 1);
		
		$usrHandle = fopen($this->getUserFileName($userName), "w");
		fwrite($usrHandle, $userContents[0] . ":" . $userContents[1] . ":" . $userContents[2] . ":" . $userContents[3] . ":" . $userContents[4] . "\n");
		fclose($usrHandle);
	}
	
	public function addUserLoss($userName)
	{
		$fileContents = file($this->userFolder . $userName . ".usr");
		$userContents = explode(":", trim($fileContents[0]));
		$losses = intval($userContents[4]);
		$userContents[4] = strval($losses + 1);
		
		$usrHandle = fopen($this->getUserFileName($userName), "w");
		fwrite($usrHandle, $userContents[0] . ":" . $userContents[1] . ":" . $userContents[2] . ":" . $userContents[3] . ":" . $userContents[4] . "\n");
		fclose($usrHandle);
	}
	
	private function getUserFileName($userName)
	{	if ($this->doesUserExist($userName))
			return $this->currentUserFiles[$userName];	
	}
	
	private function generateSalt($max = 32) {
		$baseStr = time() . rand(0, 1000000) . rand(0, 1000000);
		$md5Hash = md5($baseStr);
		if($max < 32){
			$md5Hash = substr($md5Hash, 0, $max);
		}
		return $md5Hash;
	}
	
	private function generateSecureHash($password, $salt)
	{
		return crypt($password, self::$SALT_N_ROUNDS . $salt . '$');
				
	}
	
}
?>
