<?php

	require_once 'Game.php';

class GameManager	{
	
	private $currentGameList;
	private $waitingGameList;
	private $gameFolder = "games/";
	
	private static $WAIT_EXT = "wait";
	private static $GAME_EXT = "xml";
	
	public function __construct() {
		$this->currentGameList = array();
		$this->waitingGameList = array();
		
		$gameFiles = scandir($this->gameFolder);
		foreach ($gameFiles as $gameFile)
		{
			//echo $gameFile . "<br>";
			if (is_dir($gameFile))
				continue;
			$fileInfo = pathinfo($gameFile);
			$gameID = $fileInfo["filename"];
			$gameFileName = $fileInfo["basename"];
			
			if ($fileInfo["extension"] == self::$WAIT_EXT)
			{
				$this->waitingGameList[$gameID] = $gameFileName;
			}
			else if ($fileInfo["extension"] == self::$GAME_EXT)
			{
				$this->currentGameList[$gameID] = $gameFileName;
			}
		}
				
	}
	
	public function getOngoingGames()
	{	return array_keys($this->currentGameList);	}
	
	public function getOpenGames()
	{	return array_keys($this->waitingGameList);	}
	
	public function createEmptyGame($creator)	{
		do
		{
			#Create random game number and then pad so all game numbers are a full 10 digits.
			$gameNum = rand(1, 9999999999);
			$gameID = str_pad($gameNum, 10, "0", STR_PAD_LEFT);
		}
		while (! $this->isGameIDAvailable($gameID));
		
		## NEED something to check to make sure file was created properly (dir permissions)
		$gfFP = fopen($this->gameFolder . $gameID . "." . self::$WAIT_EXT, "w");
		
		fwrite($gfFP, $creator . "\n");
		fclose($gfFP);
		$this->waitingGameList[] = $gameID;
		return $gameID;
	}
	
	public function joinGame($gameID, $challenger)	{
		$waitFile = $this->getGameWaitFile($gameID);
		if ($waitFile == "")
		{
			throw new Exception("Game ID doesn't exist");
		}
		$fileContent = file($waitFile);
		$creator = $fileContent[0];
		if ($creator == $challenger)
			throw new Exception("Creator: " . $creator . " -- Challenger: " . $challenger . ". ====> User cannout play themselves.");
		
		## NEED to pass XML file or assume Game.php will create it.
		unlink($waitFile);
		if(($key = array_search($gameID, $this->waitingGameList)) !== false) {
			unset($this->waitingGameList[$key]);
		}
		
		$this->currentGameList[$gameID] = $this->gameFolder . $gameID . "." . self::$GAME_EXT;
		$game = Game::createGame($gameID, $creator, $challenger);
		
		return $game;
	}
	
	public function getGameXML($gameID)
	{
		if ($this->isGame($gameID))
			return $this->currentGameList[$gameID];
		
		return "";
	}
	
	private function getGameWaitFile($gameID)
	{
		if (array_key_exists($gameID, $this->waitingGameList))
			return $this->waitingGameList[$gameID];
		return "";
	}
		
	public function isGameIDAvailable($gameID)
	{	return ! (array_key_exists($gameID, $this->currentGameList) || array_key_exists($gameID, $this->waitingGameList));	}
	
	public function isGame($gameID)
	{	return array_key_exists($gameID, $this->currentGameList); }
	
	
	
}

?>
