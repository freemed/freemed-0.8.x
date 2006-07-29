<?php
	// $Id$
	// $Author$

LoadObjectDependency('_FreeMED.EMRModule');

class MessageEMRView extends EMRModule {

	var $MODULE_NAME = "Patient Messages";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.1";
	var $MODULE_HIDDEN = false;

	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.0';

	// Dummy array for prototype:
	var $summary_items = array ( 1,2,3 );

	function MessageEMRView () {
		$this->acl = array ( 'bill', 'emr' );

		// Call parent constructor
		$this->EMRModule();
	} // end constructor 

	// The EMR box; probably the most important part of this module
	function summary ($patient, $num_summary_items) {
		$my_result = $GLOBALS['sql']->query("SELECT *, ".
			"MIN(msgtime) AS _msgtime ".
			"FROM messages WHERE ".
			"msgpatient='".urlencode($patient)."' ".
			"GROUP BY msgunique ".
			"ORDER BY msgtime DESC ".
			"LIMIT ".$num_summary_items);
		if ($GLOBALS['sql']->results($my_result)) {
			$buffer .= "<div style=\"overflow: auto; height: 200px;\">\n";
			$buffer .= "<table WIDTH=\"100%\" BORDER=\"0\" CELLSPACING=\"0\">\n";
			$buffer .= "<tr CLASS=\"menubar_info\">".
				"<TD><b>".__("Date")."</b></TD>".
				"<TD><b>".__("Time")."</b></TD>".
				"<TD><b>".__("Sender")."</b></TD>".
				"<TD><b>".__("Recipient")."</b></TD>".
				"<TD><b>".__("Action")."</b></TD>".
				"</tr>\n";
			while ($my_r = $GLOBALS['sql']->fetch_array($my_result)) {
				// Transformations for date and time
				$y = $m = $d = $hour = $min = '';
				if (substr($my_r['_msgtime'], 4, 1) == '-') {
					// Handle MySQL 4.1+ timestamps
					$y = substr($my_r['_msgtime'], 0, 4);
					$m = substr($my_r['_msgtime'], 5, 2);
					$d = substr($my_r['_msgtime'], 8, 2);
					$hour = substr($my_r['_msgtime'], 11, 2);
					$min  = substr($my_r['_msgtime'], 14, 2);
				} else {
					// Handle MySQL 4.0 and before timestamps
					$y = substr($my_r['_msgtime'], 0, 4);
					$m = substr($my_r['_msgtime'], 4, 2);
					$d = substr($my_r['_msgtime'], 6, 2);
					$hour = substr($my_r['_msgtime'], 8, 2);
					$min  = substr($my_r['_msgtime'], 10, 2);
				}

				$scheduler = CreateObject('FreeMED.Scheduler');
				$phyfrom = CreateObject('FreeMED.User', $my_r['msgby']);
				$phyto = CreateObject('FreeMED.User', $my_r['msgfor']);

				// Form the panel
				$buffer .= "<tr>".
					"<TD ALIGN=\"LEFT\"><SMALL>$y-$m-$d</SMALL></TD>".
					"<TD ALIGN=\"LEFT\"><SMALL>".$scheduler->get_time_string($hour,$min)."</SMALL></TD>".
					"<TD ALIGN=\"LEFT\"><SMALL>".$phyfrom->getDescription()."</SMALL></TD>".
					"<TD ALIGN=\"LEFT\"><SMALL>".$phyto->getDescription()."</SMALL></TD>".
					"<TD ALIGN=\"LEFT\">".
					html_form::confirm_link_widget(
						"messages.php?action=remove&msgpatient=".$_REQUEST['id']."&id=".$my_r['id'].
						"&return=manage", 
						"<img SRC=\"lib/template/default/img/summary_delete.png\" BORDER=\"0\" ALT=\"".__("Delete")."\"/>",
						array(
							'confirm_text' =>
							__("Messages should NOT be deleted. Are you sure you want to delete this message?"),
							'text' => __("Delete")
						)
					).
					"<a class=\"button\" href=\"messages.php?action=printview&id=".urlencode($my_r['id'])."\" target=\"msgprint\">".__("Print")."</a></td>\n".
					"</tr>\n".
					"<tr><TD COLSPAN=4 CLASS=\"infobox\"><SMALL>".
					prepare($my_r['msgtext']).
					"</SMALL></TD></tr>\n";			
			}
			$buffer .= " </table> \n";
			$buffer .= " </div> \n";
		} else {
			// If there are no messages regarding this patient
			$buffer .= __("There are currently no messages.");
		}
		return $buffer;
	} // end method summary

	// Disable summary bar
	function summary_bar() {
		$buffer .= "
		<A HREF=\"messages.php?action=addform&return=manage\">".
			__("Add")."</A>
		";
		return $buffer;
	}

} // end class MessageEMRView

register_module ("MessageEMRView");

?>
