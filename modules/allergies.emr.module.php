<?php
	// $Id$
	// $Author$

LoadObjectDependency('_FreeMED.EMRModule');

class AllergiesModule extends EMRModule {

	var $MODULE_NAME = "Allergies";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.2.1";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.6.0';

	var $record_name = "Allergies";
	var $table_name = 'allergies';
	var $patient_field = 'patient';
	var $date_field = 'reviewed';
	var $widget_hash = '##allergy## (##severity##)';

	function AllergiesModule () {
		$this->table_definition = array (
			'allergy' => SQL__VARCHAR(150),
			'severity' => SQL__VARCHAR(150),
			'patient' => SQL__INT_UNSIGNED(0),
			'reviewed' => SQL__TIMESTAMP(14),
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'allergy' => html_form::combo_assemble('allergy'),
			'severity' => html_form::combo_assemble('severity'),
			'patient',
			'reviewed' => SQL__NOW
		);

		$this->summary_vars = array (
			__("Allergy") => 'allergy',
			__("Reaction") => 'severity',
			__("Reviewed") => '_reviewed'
		);
		$this->summary_query = array (
			"DATE_FORMAT(reviewed, '%m/%d/%Y') AS _reviewed"
		);
		$this->summary_options = SUMMARY_DELETE;

		// call parent constructor
		$this->EMRModule();
	} // end constructor AllergiesModule

	function form_table ( ) {
		return array (
			__("Allergy") =>
			html_form::combo_widget(
				'allergy',
				$GLOBALS['sql']->distinct_values('allergies','allergy')
			),

			__("Reaction") =>
			html_form::combo_widget(
				'severity',
				$GLOBALS['sql']->distinct_values('allergies','severity')
			)
		);
	} // end method form_table

	function view ( ) {
		global $sql; global $display_buffer; global $patient;
		$display_buffer .= freemed_display_itemlist (
			$sql->query("SELECT * FROM ".$this->table_name." ".
				"WHERE patient='".addslashes($patient)."' ".
				freemed::itemlist_conditions(false)." ".
				"ORDER BY allergy"),
			$this->page_name,
			array(
				__("Allergy") => 'allergy',
				__("Reaction") => 'severity'
			),
			array('', __("Not specified")) //blanks
		);
	} // end method view

	function recent_text ( $patient, $recent_date = NULL ) {
		// skip recent; need all for this one
		$query = "SELECT * FROM ".$this->table_name." ".
			"WHERE ".$this->patient_field."='".addslashes($patient)."' ".
			"ORDER BY ".$this->date_field." DESC";
		$res = $GLOBALS['sql']->query($query);
		while ($r = $GLOBALS['sql']->fetch_array($res)) {
			$m[] = trim($r['allergy']).' ('.trim($r['severity']).')';
		}
		return @join(', ', $m);
	} // end method recent_text

	// Update
	function _update ( ) {
		global $sql;
		$version = freemed::module_version($this->MODULE_NAME);
		// Version 0.2
		//
		//	Migrated to seperate table ...
		//
		if (!version_check($version, '0.2')) {
			// Create new table
			$sql->query($sql->create_table_query($this->table_name, $this->table_definition, array('id')));
			// Migrate old entries
			$q = $sql->query("SELECT ptallergies,id FROM patient WHERE LENGTH(ptallergies) > 3");
			if ($sql->results($q)) {
				while ($r = $sql->fetch_array($q)) {
					$e = sql_expand($r['ptallergies']);
					foreach ($e AS $a) {
						$sql->query($sql->insert_query(
							$this->table_name,
							array(
								'allergy' => $a,								'severity' => '',
								'patient' => $r['id']	
							)
						));
					} // end foreach entry
				} // end loop through patient entries
			} // end checking for results
		}

		// Version 0.2.1
		//
		//	Add "reviewed" field
		//
		if (!version_check($version, '0.2.1')) {
			$sql->query('ALTER TABLE '.$this->table_name.' '.
				'ADD COLUMN reviewed TIMESTAMP(14) AFTER patient');
		}
	} // end method _update

} // end class AllergiesModule

register_module ("AllergiesModule");

?>
