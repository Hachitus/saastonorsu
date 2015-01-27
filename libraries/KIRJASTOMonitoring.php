<?php

/* For monitoring the site traffic.
 Google analytics etc. can handle the actual statistics, but
 this is more for the stability and against attacks. The purpose
 of it is to inform the site-admin, if the traffic is too high or
 there are possible attacks (bad IPs, too many attempt by certain user etc...)
 You can make the system so that:
 - The statistics are grouped by IP / user and daily and suspicious actions recorded:
 		For example: IP 10.0.0.0 has 50 site refreshes today and there are 3 suspicious:
 		Containing some possible SQL-code by GET-injection.
 	DB:
 	- IP, timestamp, URL-strings
 	
 	You also need a server-side CRON-JOB running for this, that would check periodically
 	if there are too many requests coming to the site. 
 	
 	It's best to be done together with cron-jobbed script. This script can make the actual statistics.
 	This way the monitoring doesn't raise the server loads to sky high, when handling page requests.
 	The actual statistics can be generated more controlled.
 	
 	DB-Requirements:
 	TABLE monitoring (
 		time
 		url
 		IP
 	)
*/

class Monitoring {
	private $IP;
	private $DBConn;
	private $URLString;
	
	function __construct(mysqli $DB) {
		$this->URLString = $_SERVER['QUERY_STRING'];
		$this->DBConn = $DB;
		$this->IP = $_SERVER['REMOTE_ADDR'];
	}
	function logSiteVisit() {
		$this->DBConn->query("INSERT INTO monitoring SET time='".$this->timestamp."', url = '".$this->URLString."', IP='".$this->IP."'");
	}
	function logSpecificAction($action) {
		$prepped = $dbh->prepare("INSERT INTO monitoring SET time=:time, url=:URL, IP=:IP, action=:action");
		$prepped->bindParam(':time', $this->timestamp);
		$prepped->bindParam(':URL', $this->URLString);
		$prepped->bindParam(':IP', $this->IP);
		$prepped->bindParam(':action', $action);
		$prepped->execute();

	}
}

?>