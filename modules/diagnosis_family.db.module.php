<?php
 // $Id$
 // note: diagnosis family module
 // lic : GPL, v2

LoadObjectDependency('_FreeMED.MaintenanceModule');

class DiagnosisFamilyModule extends MaintenanceModule {

	var $MODULE_NAME    = "Diagnosis Family Maintenance";
	var $MODULE_AUTHOR  = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.2";
	var $MODULE_DESCRIPTION = "
		Diagnosis families are part of FreeMED's attempt to
		make practice management more powerful through outcomes
		management. Diagnosis families are used to group
		diagnoses more intelligently, allowing FreeMED to
		analyze treatment patterns.
	";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.6.0';

	var $table_name     = "diagfamily";
	var $record_name    = "Diagnosis Family";
	var $order_field    = "dfname, dfdescrip";

	var $variables      = array (
		"dfname",
		"dfdescrip"
	);

	var $rpc_field_map = array (
		'name' => 'dfname',
		'description' => 'dfdescrip'
	);
	var $widget_hash = '##dfname## (##dfdescrip##)';

	function DiagnosisFamilyModule () {
		// For i18n: __("Diagnosis Family Maintenance")

		// Table definition
		$this->table_definition = array (
			'dfname' => SQL__VARCHAR(100),
			'dfdescrip' => SQL__VARCHAR(100),
			'id' => SQL__SERIAL
		);

		// Run parent constructor
		$this->MaintenanceModule();
	} // end constructor DiagnosisFamilyModule 

	function addform () { $this->view(); }

	function form () {
		global $display_buffer;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		// grab record number "id"
		if ($GLOBALS['action'] == 'modform') {
			$r = freemed::get_link_rec($id, $this->table_name);
	  		extract ($r);
			foreach ($r AS $k => $v) {
				global ${$k};
				${$k} = stripslashes($v);
			}
		}

		$display_buffer .= "
		<p/>
		<form ACTION=\"$this->page_name\" METHOD=\"POST\">
		<input TYPE=\"HIDDEN\" NAME=\"action\" VALUE=\"".
			( ($action=='modform') ? 'mod' : 'add' )."\"/>
		<input TYPE=\"HIDDEN\" NAME=\"module\" VALUE=\"".prepare($module)."\"/>
		<input TYPE=\"HIDDEN\" NAME=\"return\" VALUE=\"".prepare($_REQUEST['return'])."\"/>
		<input TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"".prepare($id)."\"/>

		<div ALIGN=\"CENTER\">
		".html_form::form_table(array(
    
		__("Name") =>
		html_form::text_widget('dfname', 20, 100),

		__("Description") =>
		html_form::text_widget('dfdescrip', 30, 100)

		))."</div>

		<div ALIGN=\"CENTER\">
		<input class=\"button\" TYPE=\"SUBMIT\" VALUE=\" ".(
 			($action=='modform') ? __("Modify") : __("Add") )." \"/>
		<input class=\"button\" TYPE=\"RESET\" VALUE=\"".__("Remove Changes")."\"/>
		</div>

		</form>
		";
	} // end method modform

	function view () {
		global $display_buffer;
		$display_buffer .= freemed_display_itemlist (
			$GLOBALS['sql']->query ( 
				"SELECT * ".
				"FROM ".$this->table_name." ".
				freemed::itemlist_conditions()." ".
				"ORDER BY $this->order_field"
			),
			$this->page_name,
			array (
				__("Name")		=>	"dfname",
				__("Description")	=>	"dfdescrip"
			),
			array ("", __("NO DESCRIPTION")), 
			"", 
			"t_page"
		);

		// Addition form at the bottom of the page
		$this->form();
	} // end method view

} // end class DiagnosisFamilyModule

register_module ("DiagnosisFamilyModule");

?>
