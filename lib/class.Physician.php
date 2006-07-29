<?php
	// $Id$
	// $Author$

// Class: FreeMED.Physician
//
//	Class object wrapper for physician/provider.
//
class Physician {
	var $local_record;                 // stores basic record
	var $id;                           // record ID for physician
	var $phylname,$phyfname,$phymname; // name of physician
	var $phyidmap;                     // id map

	// Method: Physician constructor
	//
	// Parameters:
	//
	//	$physician - Database table identifier for physician/provider.
	//
	function Physician ($physician = 0) {
		if ($physician==0) return false;    // error checking

		// Check for cache
		if (!isset($GLOBALS['__freemed']['cache']['physician'][$physician])) {
			// Get physician record
			$this->local_record = freemed::get_link_rec ($physician, "physician");

			// and cache the record
			$GLOBALS['__freemed']['cache']['physician'][$physician] = $this->local_record;
			
		} else {
			// Retrieve from cache
			$this->local_record = $GLOBALS['__freemed']['cache']['physician'][$physician];
		}
		$this->phylname     = $this->local_record["phylname"];
		$this->phyfname     = $this->local_record["phyfname"];
		$this->phymname     = $this->local_record["phymname"];
		$this->phyidmap     = unserialize($this->local_record["phyidmap"]);
	} // end constructor Physician

	// Method: fullName
	//
	//	Form full name of physician/provider.
	//
	// Parameters:
	//
	//	$use_salutation - (optional) Use Dr/Mr/Mrs instead of
	//	printing degress and certifications. Boolean, defaults
	//	to false.
	//
	// Returns:
	//
	//	Text of provider/physician's full name
	//
	function fullName ($use_salutation = false) {
		// Figure out degrees ...
		$dr = true;
		for ($i=1; $i<=3; $i++) {
			if ($this->local_record['phydeg'.$i] > 0) {
				$e = freemed::get_link_field($this->local_record['phydeg'.$i], 'degrees', 'degdegree');
				$d[] = $e;
				if (strpos($e, 'P.A.') !== false) { $dr = false; }
				if (strpos($e, 'PA') !== false) { $dr = false; }
				if (strpos($e, 'R.N.') !== false) { $dr = false; }
				if (strpos($e, 'RN') !== false) { $dr = false; }
				if (strpos($e, 'L.P.N.') !== false) { $dr = false; }
				if (strpos($e, 'LPN') !== false) { $dr = false; }
			}
		}

		// If no degrees are given, they are not a medical doctor.
		if (count($d) < 1) { $dr = false; }

		if ($use_salutation) {
			return ( $dr ? 'Dr. ' : '' ).
			$this->phyfname . " " . $this->phymname .
			( (!empty($this->phymname)) ? " " : "" ) . $this->phylname.
			( (!$dr and is_array($d)) ? ', '.join(', ', $d) : '' );
		}

		return $this->phyfname . " " .
		( (!empty($this->phymname)) ? substr($this->phymname, 0, 1).". " : "" ) . 
		$this->phylname .
		// handle degrees
		( is_array($d) ? ', '.join(', ', $d) : '' );
	} // end method fullName

	// Method: to_text
	//
	//	Generate textual representation of the current object.
	//
	// Returns:
	//
	//	Simple string consisting of the name of the current provider object.
	//
	function to_text ( ) { return $this->fullName(true); }

	// Method: getMapId
	//
	//	Retrieves a value from the phyidmap.
	//
	// Parameters:
	//
	//	$this_id - Key for value to retrieve from the map.
	//
	// Returns:
	//
	//	Value of specified key, or NULL if the key does not
	//	exist in the map.
	//
	function getMapId ($this_id = 0) {
		return ( ($this_id == 0) ? "" : $this->phyidmap[$this_id] );
	} // end method getMapId

	// Method: practiceName
	//
	//	Retrieve the full practice name for this physician/provider.
	//
	// Returns:
	//
	//	Practice name text.
	//
	function practiceName () {
		return $this->local_record["phypracname"];
	} // end method practiceName

	// Method: practicePhoneNumber
	//
	//	Retrieve the providers' practice phone number.
	//
	// Returns:
	//
	//	Formatted phone number.
	//
	function practicePhoneNumber ( ) {
		return freemed::phone_display ( $this->local_record['phyphonea'] );
	} // end method practicePhoneNumber

} // end class Physician

?>
