<?php
	// $Id$
	// $Author$

	// Handler for HL7 message S15 - Appointment Cancellation

LoadObjectDependency('_FreeMED.Handler_HL7v2');

class Handler_HL7v2_S15 extends Handler_HL7v2 {

	function Handle () {
		syslog(LOG_INFO, 'HL7 parser| Entered S15 parser');
		if (!is_object($this->parser)) {
			die('Handler_HL7v2_S15: parser object not present');
		}

		// For now, one PID per message - FIXME
		$p = $this->parser->message['PID'][0];
		foreach ($this->parser->message['SCH'] AS $k => $v) {
			$pr = $this->parser->message['AIP'][$k];

			// Let's do this the backwards way:
			//
			// Use the same logic as the S12, but do a DELETE
			// SQL query. Hopefully that won't break anything
			// else. - Jeff

			// Use scheduler API
			$c = CreateObject('_FreeMED.Scheduler');
			$query = "DELETE FROM scheduler WHERE ".
				"caltype = 'pat' AND ". // hardcode as patient
				"caldateof = '".addslashes($this->parser->__date_to_sql($pr[HL7v2_AIP_DATETIME]))."' AND ".
				"calhour = '".addslashes($this->parser->__date_to_hour($pr[HL7v2_AIP_DATETIME]))."' AND ".
				"calminute = '".addslashes($this->parser->__date_to_minute($pr[HL7v2_AIP_DATETIME]))."' AND ".
				//"calduration = '".addslashes($pr[HL7v2_AIP_DURATION]+0)."' AND ".
				//calprovider = $this->parser->__aip_to_provider($pr[HL7v2_AIP_PROVIDER][HL7v2_AIP_PROVIDER_ID]),
				"calpatient = '".addslashes($this->parser->__pid_to_patient($p[HL7v2_PID_ID]))."'";
			$result = $GLOBALS['sql']->query($query);

			// Quickly log what has happened
			syslog(LOG_INFO, 'HL7 parser| deleted S15 appointment record for patient #'.$this->parser->__pid_to_patient($p[HL7v2_PID_ID]).', provider #'.$this->parser->__aip_to_provider($pr[HL7v2_AIP_PROVIDER][HL7v2_AIP_PROVIDER_ID]).' at '.$this->parser->__date_to_hour($pr[HL7v2_AIP_DATETIME]).':'.$this->parser->__date_to_minute($pr[HL7v2_AIP_DATETIME]));
			syslog(LOG_INFO, 'HL7 parser| sql query = '.$query.', result = '.$result);
		}
	} // end method Handle

	function Type () { return 'SIU'; }

} // end class Handler_HL7v2_S15

?>
