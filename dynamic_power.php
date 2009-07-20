#!/usr/bin/php
<?php
class dynamicPowerManagement
{
	/*
	The data is retrieved from Tom Armitage's canituriton.com website.
	I believe he gets this data from the nationalgrid site - but I use his as it has a nice api - Thanks Tom :) 
	*/
	public $debug = false;
	public $data_url = 'http://caniturniton.com/api/xml';
	public $data_xml;

	private $xml_response, $high_display, $high_disk, $high_sleep, $low_display, $low_disk, $low_sleep;
	private $womp = 1;
	
	function dynamicPowerManagement($high_display=10, $high_disk=20, $high_sleep=30, $low_display=2, $low_disk=3, $low_sleep=5)
	{
		$this->high_display = $high_display;																			//Should all be in minutes and of course if you're daft and put in nothing or chars then it will fail horribly.
		$this->high_disk = $high_disk;
		$this->high_sleep = $high_sleep;
		$this->low_display = $low_display;
		$this->low_disk = $low_disk;
		$this->low_sleep = $low_sleep;
		
		if($this->retrieveData())																						//Get the data from the caniturniton api
		{
			$this->xml_response = $this->parseXML();																	//Parse the XML
			$this->setPMOnDecision();																					//Make the decision
		} else {
			echo 'error!';
		}
		exit();																											//Just to be sure nothing hangs around
	}
	
	private function retrieveData()																						//Get the data from the datasource using CURL
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->data_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		$this->data_xml = curl_exec ($ch);
		curl_close ($ch);
		if($this->data_xml != '') return true;
		return false;
	}
	
	private function parseXML()																							//Use SimpleXML to parse the returned data
	{
		$r = simplexml_load_string($this->data_xml);
		return $r;
	}
	
	private function setPMOnDecision()																					//Yes I know Tom makes a calulation but I wanted to do it for myself
	{
		if($this->xml_response->frequency < 50) {
			$this->debug ? print('NO') : exec('pmset -a displaysleep '.$this->low_display.' disksleep '.$this->low_disk.' sleep '.$this->low_sleep.' womp '.$this->womp); 					//No - there is more demand than supply
		} elseif ($this->xml_response->frequency > 50) {
			$this->debug ? print('YES') : exec('pmset -a displaysleep '.$this->high_display.' disksleep '.$this->high_disk.' sleep '.$this->high_sleep.' womp '.$this->womp); 				//Yes - there is more supply than demand
		} elseif ($this->xml_response->frequency == 50) {
			$this->debug ? print('BALANCED') : exec('pmset -a displaysleep '.$this->high_display.' disksleep '.$this->high_disk.' sleep '.$this->high_sleep.' womp '.$this->womp); 			//The grid is balanced - supply == demand
		} else {
			echo 'Hmmm something\'s gone wrong with the feed.';
		}
	}
}

//Let's go!
$newPowerSet = new dynamicPowerManagement();
?>