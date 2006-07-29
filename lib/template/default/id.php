<?php
 // $Id$
 // $Author$
 // desc: contains identifying information about the template

$TEMPLATE_NAME = "Default";
$TEMPLATE_DESC = "FreeMED default theme (0.6)";
$TEMPLATE_AUTHOR = "jeff b";
$TEMPLATE_OPTIONS = array (
	array(
		'name'    => __("Color Scheme"),
		'var'     => "stylesheet",
		'options' => array (
			__("Default Blue") => "stylesheet.css"
		)
	),

	array(
		'name'    => __("Display Language Selection"),
		'var'     => "language_bar",
		'options' => array (
			__("Yes") => '1',
			__("No")  => '0'
		)
	)
);

?>
