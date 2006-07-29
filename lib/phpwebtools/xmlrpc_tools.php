<?php
 // $Id$
 // $Author$
 //
 // $Log: xmlrpc_tools.php,v $
 // Revision 1.3  2002/11/04 13:41:49  rufustfirefly
 // xmlrpc_decode() -> xmlrpc_php_decode() for namespace reasons.
 //
 // Revision 1.2  2002/11/04 04:05:58  rufustfirefly
 // xmlrpc_encode() -> xmlrpc_php_encode() to avoid conflicts
 //
 // Revision 1.1  2002/10/17 15:51:02  rufustfirefly
 // Import of XMLRPC server and client pieces (modified from phpgroupware).
 //

// Internal function for preparing raw values for XML-RPC transport
function xmlrpc_prepare ($value) {
	return xmlrpc_php_encode(stripslashes($value));
} // end function xmlrpc_prepare

// Internal function for registering methods with XML-RPC server
function xmlrpc_register ($method, $doc='') {
	global $xmlrpc_server, $XMLRPC_METHODS;
	$func = str_replace(".", "_", $method);
	$XMLRPC_METHODS[$method] = array(
		'function'  => $func,
		'docstring' => $doc
	);
} // end function xmlrpc_register

// Following code is:
// by Edd Dumbill (C) 1999-2001
// <edd@usefulinc.com>
// xmlrpc.inc,v 1.18 2001/07/06 18:23:57 edmundd

if (!function_exists('xml_parser_create'))
{
	// Win 32 fix. From: 'Leo West' <lwest@imaginet.fr>
	if($WINDIR)
	{
		dl('php3_xml.dll');
	}
	else
	{
		dl('xml.so');
	}
}

	define('xmlrpcI4','i4');
	define('xmlrpcInt','int');
	define('xmlrpcBoolean','boolean');
	define('xmlrpcDouble','double');
	define('xmlrpcString','string');
	define('xmlrpcDateTime','dateTime.iso8601');
	define('xmlrpcBase64','base64');
	define('xmlrpcArray','array');
	define('xmlrpcStruct','struct');

	$GLOBALS['xmlrpcTypes'] = array(
		xmlrpcI4       => 1,
		xmlrpcInt      => 1,
		xmlrpcBoolean  => 1,
		xmlrpcString   => 1,
		xmlrpcDouble   => 1,
		xmlrpcDateTime => 1,
		xmlrpcBase64   => 1,
		xmlrpcArray    => 2,
		xmlrpcStruct   => 3
	);

	$GLOBALS['xmlEntities']=array(
		'amp'  => '&',
		'quot' => '"',
		'lt'   => '<',
		'gt'   => '>',
		'apos' => "'"
	);

	$GLOBALS['xmlrpcerr']['unknown_method']     = 1;
	$GLOBALS['xmlrpcstr']['unknown_method']     = 'Unknown method';
	$GLOBALS['xmlrpcerr']['invalid_return']     = 2;
	$GLOBALS['xmlrpcstr']['invalid_return']     = 'Invalid return payload: enabling debugging to examine incoming payload';
	$GLOBALS['xmlrpcerr']['incorrect_params']   = 3;
	$GLOBALS['xmlrpcstr']['incorrect_params']   = 'Incorrect parameters passed to method';
	$GLOBALS['xmlrpcerr']['introspect_unknown'] = 4;
	$GLOBALS['xmlrpcstr']['introspect_unknown'] = "Can't introspect: method unknown";
	$GLOBALS['xmlrpcerr']['http_error']         = 5;
	$GLOBALS['xmlrpcstr']['http_error']         = "Didn't receive 200 OK from remote server.";
	$GLOBALS['xmlrpcerr']['no_data']            = 6;
	$GLOBALS['xmlrpcstr']['no_data']            = 'No data received from server.';
	$GLOBALS['xmlrpcerr']['no_ssl']             = 7;
	$GLOBALS['xmlrpcstr']['no_ssl']             = 'No SSL support compiled in.';
	$GLOBALS['xmlrpcerr']['curl_fail']          = 8;
	$GLOBALS['xmlrpcstr']['curl_fail']          = 'CURL error';
	$GLOBALS['xmlrpcerr']['no_access']          = 9;
	$GLOBALS['xmlrpcstr']['no_access']          = 'Access denied';

	$GLOBALS['xmlrpc_defencoding'] = 'UTF-8';

	$GLOBALS['xmlrpcName']    = 'XML-RPC for PHP';
	$GLOBALS['xmlrpcVersion'] = '1.0b9';

	// let user errors start at 800
	$GLOBALS['xmlrpcerruser'] = 800; 
	// let XML parse errors start at 100
	$GLOBALS['xmlrpcerrxml'] = 100;

	// formulate backslashes for escaping regexp
	$GLOBALS['xmlrpc_backslash'] = chr(92) . chr(92);

	/*!
	@function xmlrpcfault
	@abstract Error reporting for XML-RPC
	@discussion Author: jengo <br>
	Returns XML-RPC fault and stops this execution of the application. <br>
	Syntax: void xmlrpcfault(string) <br>
	Example1: xmlrpcfault('Session could not be verifed'); <br>
	@param $string Error message to be returned.
	*/
	function xmlrpcfault($string)
	{
		$r = CreateObject('PHP.xmlrpcresp',
			CreateObject('PHP.xmlrpcval'),
			$GLOBALS['xmlrpcerr']['unknown_method'],
			$string
		);
		$payload = '<?xml version="1.0"?>' . "\n" . $r->serialize();
		Header('Content-type: text/xml');
		Header('Content-length: ' . strlen($payload));
		print $payload;
		die();
	}

	// used to store state during parsing
	// quick explanation of components:
	//   st - used to build up a string for evaluation
	//   ac - used to accumulate values
	//   qt - used to decide if quotes are needed for evaluation
	//   cm - used to denote struct or array (comma needed)
	//   isf - used to indicate a fault
	//   lv - used to indicate 'looking for a value': implements
	//        the logic to allow values with no types to be strings
	//   params - used to store parameters in method calls
	//   method - used to store method name

	$GLOBALS['_xh']=array();

	function xmlrpc_entity_decode($string)
	{
		$top = split('&', $string);
		$op  = '';
		$i   = 0;
		while($i<sizeof($top))
		{
			if (ereg("^([#a-zA-Z0-9]+);", $top[$i], $regs))
			{
				$op .= ereg_replace("^[#a-zA-Z0-9]+;",
					xmlrpc_lookup_entity($regs[1]), $top[$i]);
			}
			else
			{
				if ($i == 0) 
				{
					$op = $top[$i];
				}
				else
				{
					$op .= '&' . $top[$i];
				}
			}
			$i++;
		}
		return $op;
	}

	function xmlrpc_lookup_entity($ent)
	{
		if (isset($GLOBALS['xmlEntities'][strtolower($ent)]))
		{
			return $GLOBALS['xmlEntities'][strtolower($ent)];
		}
		if (ereg("^#([0-9]+)$", $ent, $regs))
		{
			return chr($regs[1]);
		}
		return '?';
	}

	function xmlrpc_se($parser, $name, $attrs)
	{
		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				$GLOBALS['_xh'][$parser]['st'] .= 'array(';
				$GLOBALS['_xh'][$parser]['cm']++;
				// this last line turns quoting off
				// this means if we get an empty array we'll 
				// simply get a bit of whitespace in the eval
				$GLOBALS['_xh'][$parser]['qt']=0;
				break;
			case 'NAME':
				$GLOBALS['_xh'][$parser]['st'] .= "'";
				$GLOBALS['_xh'][$parser]['ac'] = '';
				break;
			case 'FAULT':
				$GLOBALS['_xh'][$parser]['isf'] = 1;
				break;
			case 'PARAM':
				$GLOBALS['_xh'][$parser]['st'] = '';
				break;
			case 'VALUE':
				$GLOBALS['_xh'][$parser]['st'] .= " CreateObject('PHP.xmlrpcval',"; 
				$GLOBALS['_xh'][$parser]['vt']  = xmlrpcString;
				$GLOBALS['_xh'][$parser]['ac']  = '';
				$GLOBALS['_xh'][$parser]['qt']  = 0;
				$GLOBALS['_xh'][$parser]['lv']  = 1;
				// look for a value: if this is still 1 by the
				// time we reach the first data segment then the type is string
				// by implication and we need to add in a quote
				break;
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'BOOLEAN':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				$GLOBALS['_xh'][$parser]['ac']=''; // reset the accumulator

				if ($name=='DATETIME.ISO8601' || $name=='STRING')
				{
					$GLOBALS['_xh'][$parser]['qt']=1;
					if ($name=='DATETIME.ISO8601')
					{
						$GLOBALS['_xh'][$parser]['vt']=xmlrpcDateTime;
					}
				}
				elseif($name=='BASE64')
				{
					$GLOBALS['_xh'][$parser]['qt']=2;
				}
				else
				{
					// No quoting is required here -- but
					// at the end of the element we must check
					// for data format errors.
					$GLOBALS['_xh'][$parser]['qt']=0;
				}
				break;
			case 'MEMBER':
				$GLOBALS['_xh'][$parser]['ac']='';
				break;
			default:
				break;
		}

		if ($name!='VALUE')
		{
			$GLOBALS['_xh'][$parser]['lv']=0;
		}
	}

	function xmlrpc_ee($parser, $name)
	{
		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				if ($GLOBALS['_xh'][$parser]['cm'] && substr($GLOBALS['_xh'][$parser]['st'], -1) ==',')
				{
					$GLOBALS['_xh'][$parser]['st']=substr($GLOBALS['_xh'][$parser]['st'],0,-1);
				}
				$GLOBALS['_xh'][$parser]['st'].=')';
				$GLOBALS['_xh'][$parser]['vt']=strtolower($name);
				$GLOBALS['_xh'][$parser]['cm']--;
				break;
			case 'NAME':
				$GLOBALS['_xh'][$parser]['st'].= $GLOBALS['_xh'][$parser]['ac'] . "' => ";
				break;
			case 'BOOLEAN':
				// special case here: we translate boolean 1 or 0 into PHP
				// constants true or false
				if ($GLOBALS['_xh'][$parser]['ac']=='1') 
				{
					$GLOBALS['_xh'][$parser]['ac']='True';
				}
				else
				{
					$GLOBALS['_xh'][$parser]['ac']='false';
				}
				$GLOBALS['_xh'][$parser]['vt']=strtolower($name);
				// Drop through intentionally.
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				if ($GLOBALS['_xh'][$parser]['qt']==1)
				{
					// we use double quotes rather than single so backslashification works OK
					$GLOBALS['_xh'][$parser]['st'].='"'. $GLOBALS['_xh'][$parser]['ac'] . '"'; 
				}
				elseif ($GLOBALS['_xh'][$parser]['qt']==2)
				{
					$GLOBALS['_xh'][$parser]['st'].="base64_decode('". $GLOBALS['_xh'][$parser]['ac'] . "')"; 
				}
				elseif ($name=='BOOLEAN')
				{
					$GLOBALS['_xh'][$parser]['st'].=$GLOBALS['_xh'][$parser]['ac'];
				}
				else
				{
					// we have an I4, INT or a DOUBLE
					// we must check that only 0123456789-.<space> are characters here
					if (!ereg("^\-?[0123456789 \t\.]+$", $GLOBALS['_xh'][$parser]['ac']))
					{
						// TODO: find a better way of throwing an error
						// than this!
						error_log('XML-RPC: non numeric value received in INT or DOUBLE');
						$GLOBALS['_xh'][$parser]['st'].='ERROR_NON_NUMERIC_FOUND';
					}
					else
					{
						// it's ok, add it on
						$GLOBALS['_xh'][$parser]['st'].=$GLOBALS['_xh'][$parser]['ac'];
					}
				}
				$GLOBALS['_xh'][$parser]['ac'] = '';
				$GLOBALS['_xh'][$parser]['qt'] = 0;
				$GLOBALS['_xh'][$parser]['lv'] = 3; // indicate we've found a value
				break;
			case 'VALUE':
				// deal with a string value
				if (strlen($GLOBALS['_xh'][$parser]['ac'])>0 &&
					$GLOBALS['_xh'][$parser]['vt']==xmlrpcString)
				{
					$GLOBALS['_xh'][$parser]['st'].='"'. $GLOBALS['_xh'][$parser]['ac'] . '"'; 
				}
				// This if() detects if no scalar was inside <VALUE></VALUE>
				// and pads an empty ''.
				if($GLOBALS['_xh'][$parser]['st'][strlen($GLOBALS['_xh'][$parser]['st'])-1] == '(')
				{
					$GLOBALS['_xh'][$parser]['st'].= '""';
				}
				$GLOBALS['_xh'][$parser]['st'].=", '" . $GLOBALS['_xh'][$parser]['vt'] . "')";
				if ($GLOBALS['_xh'][$parser]['cm'])
				{
					$GLOBALS['_xh'][$parser]['st'].=',';
				}
				break;
			case 'MEMBER':
				$GLOBALS['_xh'][$parser]['ac']='';
				$GLOBALS['_xh'][$parser]['qt']=0;
				break;
			case 'DATA':
				$GLOBALS['_xh'][$parser]['ac']='';
				$GLOBALS['_xh'][$parser]['qt']=0;
				break;
			case 'PARAM':
				$GLOBALS['_xh'][$parser]['params'][]=$GLOBALS['_xh'][$parser]['st'];
				break;
			case 'METHODNAME':
				$GLOBALS['_xh'][$parser]['method']=ereg_replace("^[\n\r\t ]+", '', $GLOBALS['_xh'][$parser]['ac']);
				break;
			case 'BOOLEAN':
				// special case here: we translate boolean 1 or 0 into PHP
				// constants true or false
				if ($GLOBALS['_xh'][$parser]['ac']=='1') 
				{
					$GLOBALS['_xh'][$parser]['ac']='True';
				}
				else
				{
					$GLOBALS['_xh'][$parser]['ac']='false';
				}
				$GLOBALS['_xh'][$parser]['vt']=strtolower($name);
				break;
			default:
				break;
		}
		// if it's a valid type name, set the type
		if (isset($GLOBALS['xmlrpcTypes'][strtolower($name)]))
		{
			$GLOBALS['_xh'][$parser]['vt']=strtolower($name);
		}
	}

	function xmlrpc_cd($parser, $data)
	{
		//if (ereg("^[\n\r \t]+$", $data)) return;
		// print "adding [${data}]\n";

		if ($GLOBALS['_xh'][$parser]['lv']!=3)
		{
			// 'lookforvalue==3' means that we've found an entire value
			// and should discard any further character data
			if ($GLOBALS['_xh'][$parser]['lv']==1)
			{
				// if we've found text and we're just in a <value> then
				// turn quoting on, as this will be a string
				$GLOBALS['_xh'][$parser]['qt']=1; 
				// and say we've found a value
				$GLOBALS['_xh'][$parser]['lv']=2; 
			}
			$GLOBALS['_xh'][$parser]['ac'].= addslashes(
				str_replace(chr(92),$GLOBALS['xmlrpc_backslash'], $data));
		}
	}

	function xmlrpc_dh($parser, $data)
	{
		if (substr($data, 0, 1) == '&' && substr($data, -1, 1) == ';')
		{
			if ($GLOBALS['_xh'][$parser]['lv']==1)
			{
				$GLOBALS['_xh'][$parser]['qt']=1; 
				$GLOBALS['_xh'][$parser]['lv']=2; 
			}
			$GLOBALS['_xh'][$parser]['ac'].=str_replace('$', '\$',
				str_replace('"', '\"', 
				str_replace(chr(92),$GLOBALS['xmlrpc_backslash'], $data)));
		}
	}

	// date helpers
	function iso8601_encode($timet, $utc=0)
	{
		// return an ISO8601 encoded string
		// really, timezones ought to be supported
		// but the XML-RPC spec says:
		//
		// "Don't assume a timezone. It should be specified by the server in its
		// documentation what assumptions it makes about timezones."
		// 
		// these routines always assume localtime unless 
		// $utc is set to 1, in which case UTC is assumed
		// and an adjustment for locale is made when encoding
		if (!$utc)
		{
			$t=strftime('%Y%m%dT%H:%M:%S', $timet);
		}
		else
		{
			if (function_exists('gmstrftime')) 
			{
				// gmstrftime doesn't exist in some versions
				// of PHP
				$t=gmstrftime('%Y%m%dT%H:%M:%S', $timet);
			}
			else
			{
				$t=strftime('%Y%m%dT%H:%M:%S', $timet-date('Z'));
			}
		}
		return $t;
	}

	function iso8601_decode($idate, $utc=0)
	{
		// return a timet in the localtime, or UTC
		$t=0;
		if (ereg("([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})",$idate, $regs))
		{
			if ($utc)
			{
				$t=gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			}
			else
			{
				$t=mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			}
		} 
		return $t;
	}

	/****************************************************************
	* xmlrpc_php_decode takes a message in PHP xmlrpc object format *
	* and tranlates it into native PHP types.                       *
	*                                                               *
	* author: Dan Libby (dan@libby.com)                             *
	****************************************************************/
	function xmlrpc_php_decode($xmlrpc_val)
	{
		$kind = @$xmlrpc_val->kindOf();

		if($kind == 'scalar')
		{
			return $xmlrpc_val->scalarval();
		}
		elseif($kind == 'array')
		{
			$size = $xmlrpc_val->arraysize();
			$arr = array();

			for($i = 0; $i < $size; $i++)
			{
				$arr[]=xmlrpc_php_decode($xmlrpc_val->arraymem($i));
			}
			return $arr; 
		}
		elseif($kind == 'struct')
		{
			$xmlrpc_val->structreset();
			$arr = array();

			while(list($key,$value)=$xmlrpc_val->structeach())
			{
				$arr[$key] = xmlrpc_php_decode($value);
			}
			return $arr;
		}
	}

	/****************************************************************
	* xmlrpc_php_encode takes native php types and encodes them into*
	* xmlrpc PHP object format.                                     *
	* BUG: All sequential arrays are turned into structs.  I don't  *
	* know of a good way to determine if an array is sequential     *
	* only.                                                         *
	*                                                               *
	* feature creep -- could support more types via optional type   *
	* argument.                                                     *
	*                                                               *
	* author: Dan Libby (dan@libby.com)                             *
	****************************************************************/
	function xmlrpc_php_encode($php_val)
	{
		$type = gettype($php_val);
		$xmlrpc_val = CreateObject('PHP.xmlrpcval');

		switch($type)
		{
			case 'array':
			case 'object':
				$arr = array();
				while (list($k,$v) = each($php_val))
				{
					$arr[$k] = xmlrpc_php_encode($v);
				}
				$xmlrpc_val->addStruct($arr);
				break;
			case 'integer':
				$xmlrpc_val->addScalar($php_val, xmlrpcInt);
				break;
			case 'double':
				$xmlrpc_val->addScalar($php_val, xmlrpcDouble);
				break;
			case 'string':
				$xmlrpc_val->addScalar($php_val, xmlrpcString);
				break;
			// <G_Giunta_2001-02-29>
			// Add support for encoding/decoding of booleans, since they are supported in PHP
			case 'boolean':
				$xmlrpc_val->addScalar($php_val, xmlrpcBoolean);
				break;
			// </G_Giunta_2001-02-29>
			case 'unknown type':
			default:
				$xmlrpc_val = false;
				break;
		}
		return $xmlrpc_val;
	}

	// listMethods: either a string, or nothing
	$GLOBALS['_xmlrpcs_listMethods_sig'] = array(array(xmlrpcArray, xmlrpcString), array(xmlrpcArray));
	$GLOBALS['_xmlrpcs_listMethods_doc'] = 'This method lists all the methods that the XML-RPC server knows how to dispatch';
	function _xmlrpcs_listMethods($server, $m)
	{
		$v     =  CreateObject('PHP.xmlrpcval');
		$dmap  = $server->dmap;
		$outAr = array();
		for(reset($dmap); list($key, $val) = each($dmap); )
		{
			$outAr[] = CreateObject('PHP.xmlrpcval',$key, 'string');
		}
		$dmap = $GLOBALS['_xmlrpcs_dmap'];
		for(reset($dmap); list($key, $val) = each($dmap); )
		{
			$outAr[] = CreateObject('PHP.xmlrpcval',$key, 'string');
		}
		$v->addArray($outAr);
		return CreateObject('PHP.xmlrpcresp',$v);
	}

	$GLOBALS['_xmlrpcs_methodSignature_sig']=array(array(xmlrpcArray, xmlrpcString));
	$GLOBALS['_xmlrpcs_methodSignature_doc']='Returns an array of known signatures (an array of arrays) for the method name passed. If no signatures are known, returns a none-array (test for type != array to detect missing signature)';
	function _xmlrpcs_methodSignature($server, $m)
	{
		$methName = $m->getParam(0);
		$methName = $methName->scalarval();
		if (ereg("^system\.", $methName))
		{
			$dmap = $GLOBALS['_xmlrpcs_dmap'];
			$sysCall = 1;
		}
		else
		{
			$dmap = $server->dmap;
			$sysCall = 0;
		}
		//	print "<!-- ${methName} -->\n";
		if (isset($dmap[$methName]))
		{
			if ($dmap[$methName]['signature'])
			{
				$sigs = array();
				$thesigs=$dmap[$methName]['signature'];
				for($i=0; $i<sizeof($thesigs); $i++)
				{
					$cursig = array();
					$inSig  = $thesigs[$i];
					for($j=0; $j<sizeof($inSig); $j++)
					{
						$cursig[] = CreateObject('PHP.xmlrpcval',$inSig[$j], 'string');
					}
					$sigs[] = CreateObject('PHP.xmlrpcval',$cursig, 'array');
				}
				$r = CreateObject('PHP.xmlrpcresp',CreateObject('PHP.xmlrpcval',$sigs, 'array'));
			}
			else
			{
				$r = CreateObject('PHP.xmlrpcresp', CreateObject('PHP.xmlrpcval','undef', 'string'));
			}
		}
		else
		{
			$r = CreateObject('PHP.xmlrpcresp',0,$GLOBALS['xmlrpcerr']['introspect_unknown'],$GLOBALS['xmlrpcstr']['introspect_unknown']);
		}
		return $r;
	}

	$GLOBALS['_xmlrpcs_methodHelp_sig'] = array(array(xmlrpcString, xmlrpcString));
	$GLOBALS['_xmlrpcs_methodHelp_doc'] = 'Returns help text if defined for the method passed, otherwise returns an empty string';
	function _xmlrpcs_methodHelp($server, $m)
	{
		$methName = $m->getParam(0);
		$methName = $methName->scalarval();
		if (ereg("^system\.", $methName))
		{
			$dmap = $GLOBALS['_xmlrpcs_dmap'];
			$sysCall=1;
		}
		else
		{
			$dmap = $server->dmap;
			$sysCall=0;
		}
		//	print "<!-- ${methName} -->\n";
		if (isset($dmap[$methName]))
		{
			if ($dmap[$methName]['docstring'])
			{
				$r = CreateObject('PHP.xmlrpcresp', CreateObject('PHP.xmlrpcval',$dmap[$methName]['docstring']),'string');
			}
			else
			{
				$r = CreateObject('PHP.xmlrpcresp', CreateObject('PHP.xmlrpcval'), 'string');
			}
		}
		else
		{
			$r = CreateObject('PHP.xmlrpcresp',0,$GLOBALS['xmlrpcerr']['introspect_unknown'],$GLOBALS['xmlrpcstr']['introspect_unknown']);
		}
		return $r;
	}

	$GLOBALS['_xmlrpcs_dmap'] = array(
		'system.listMethods' => array(
			'function'  => '_xmlrpcs_listMethods',
			'signature' => $GLOBALS['_xmlrpcs_listMethods_sig'],
			'docstring' => $GLOBALS['_xmlrpcs_listMethods_doc']
		),
		'system.methodSignature' => array(
			'function'  => '_xmlrpcs_methodSignature',
			'signature' => $GLOBALS['_xmlrpcs_methodSignature_sig'],
			'docstring' => $GLOBALS['_xmlrpcs_methodSignature_doc']
		),
		'system.methodHelp' => array(
			'function'  => '_xmlrpcs_methodHelp',
			'signature' => $GLOBALS['_xmlrpcs_methodHelp_sig'],
			'docstring' => $GLOBALS['_xmlrpcs_methodHelp_doc']
		)
	);

	$GLOBALS['_xmlrpc_debuginfo'] = '';
	function xmlrpc_debugmsg($m)
	{
		$GLOBALS['_xmlrpc_debuginfo'] .= $m . "\n";
	}
?>
