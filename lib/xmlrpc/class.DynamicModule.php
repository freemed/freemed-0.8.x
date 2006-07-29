<?php
	// $Id$
	// $Author$
	// Defines FreeMED.DynamicModule.* namespace

	// This XML-RPC module handles all dynamic/loadable modules in
	// FreeMED. Individual calls are made by:
	// 	FreeMED.DynamicModule.[call]([function], [params])
	// ex: FreeMED.DyanamicModule.picklist('FacilityModule')

class DynamicModule {

	function add ($module, $param) {
		// Wrapper for _addmod
		return DynamicModule::_addmod('add', $module, $param);
	} // end method add

	function update ($module, $param) {
		// Wrapper for _addmod
		return DynamicModule::_addmod('mod', $module, $param);
	} // end method update 

	function remove ($module, $id) {
		// Load module list
		$module_list = freemed::module_cache();

		// Check for name in hash
		$resolved = check_module($module);
		if (!$resolved) {
			// TODO: Return error
			return false;	
		}

		// Run proper module
		return module_function($module, '_del', $id);
	} // end method remove

	function picklist ($module, $params=NULL) {
		// Load module list
		$module_list = freemed::module_cache();

		// Check for name in hash
		$resolved = check_module($module);
		if (!$resolved) {
			// TODO: Return error
			return false;	
		}

		// Run proper module
		return module_function($module, 'picklist', array($params));
	} // end method picklist

	function distinct ($module, $param) {
		// Load module list
		$module_list = freemed::module_cache();

		// Check for name in hash
		$resolved = check_module($module);
		if (!$resolved) {
			// TODO: Return error
			return false;	
		}

		// Run proper module
		return module_function($module, 'distinct', array($param));
	} // end method distinct

	// ----- Internal functions -----------------------------------------

	function _addmod ($type, $module, $param) {
		// Stop any mickey-mousing
		if ($type != 'add' and type != 'mod') return false;

		//print "module = $module\n";
		//print "param = "; print_r($param); print "\n";

		// Load module list
		$module_list = freemed::module_cache();

		// Check for name in hash
		$resolved = check_module($module);
		if (!$resolved) {
			// TODO: Return error
			return false;	
		}

		// Translate hash from meta-information associated with
		// the module
		$trans_table = freemed::module_get_meta($module, 'rpc_field_map');
		/*
		 // FOR NOW DISABLE MAPPING, USE RAW FIELDS
		foreach ($param AS $k => $v) {
			// If there is a mapping, pass it through to the
			// hash for a real parameter ... if not, it's probably
			// a bad idea
			if (isset($trans_table[$k])) {
				//print "mapped $k to ".$trans_table[$k]." ($v) \n";
				$hash[$trans_table[$k]] = $v;
			} else {
				//print "discarded bad mapping $k\n";
			}
		}
		*/

		// Publish fields as given to _REQUEST, to hack around any
		// bizarre requests for field names ... this probably needs
		// very badly to be fixed or standardized in some way.
		$hash = $param;
		// SHOULD BE DONE IN MaintenanceModule and/or EMRModule
		//foreach ($hash AS $k => $v) {
		//	global ${$k}; ${$k} = $v;
		//	$_REQUEST[$k] = $v;
		//}

		// Run proper module method, determined by type parameter
		return module_function($module, '_'.$type, array($hash));
	} // end method add

} // end class DynamicModule

?>
