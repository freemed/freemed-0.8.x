<?php

 // $Id$
 // $Author$
 // code: jeff b (jeff@ourexchange.net)
 // lic : GPL, v2

$page_name = "index.php";

// Fred Trotter: I have seperated all of the health checks that the system
// should perform to this file. It should catch the following configuration 
// problems...
// 1. PHP not installed (accomplished by index.html)
// 2. Data Base connection failure
// 3. Data Base selection failure
// 4. Uninitialized database failure
// These have now been modularized by jeff...
// In order to accomblish this jeff uses phpwebtools, so I have moved to check for php webtools
// to this file...

// Import settings from our global settings file
include_once(dirname(__FILE__).'/lib/settings.php');
require_once(dirname(__FILE__).'/lib/phpwebtools/webtools.php');

define('SKIP_SQL_INIT', true);
include_once (dirname(__FILE__).'/lib/freemed.php');

// Deal with removing auth information from the session
unset($_SESSION['authdata']);

$test = CreateObject('FreeMED.FreeMEDSelfTest');

if (ALWAYS_SELFTEST) {
	$test->SelfTest();
}

// Unfortunately, we have to *manually* create the SQL object after selftest
if (!is_object($sql)) {
	$sql = CreateObject(
		'PHP.sql',
		DB_ENGINE,
		array(
			'host' => DB_HOST,
			'user' => DB_USER,
			'password' => DB_PASSWORD,
			'database' => DB_NAME
		)
	);
}

//----- Set page title
$page_title = PACKAGENAME . " - " . __("Login");

//----- Set no menu bar for login screen
$GLOBALS['__freemed']['no_menu_bar'] = true;

//----- *DON'T* Reset default facility session cookie

//----- Load template with main menu
if (!freemed::connect()) {
	include_once (freemed::template_file('login.php'));
} else {
	Header("Location: main.php");
}

//----- Finish display template
template_display();

?>
