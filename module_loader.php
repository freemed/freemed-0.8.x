<?php
 // $Id$
 // desc: module loader
 // lic : GPL, v2

include_once ("lib/freemed.php");

//----- Load object dependencies for module methods
LoadObjectDependency('PHP.module');

//----- Get list of modules
$module_list = freemed::module_cache();

//----- Check for module
if (!$module_list->check_for($module)) {
	$display_buffer .= "Module \"$module\" not found<br/>\n";
	template_display();
} // end of checking for module

//----- Load specified module
execute_module ($module);

//----- Display template
template_display();

?>
