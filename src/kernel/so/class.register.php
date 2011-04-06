<?php
/**
 * \file
 * Define the abstract Register class.
 * \version $Id: class.register.php,v 1.5 2011-04-06 14:42:16 oscar Exp $
 */


/**
 * \name Bitmaps
 * These bitmaps define the layout of status codes. They are used to extract information from the code
 * @{
 */

/**
 * Bits 1-8 define the application.
 * Application identifiers with the first bit set (0x40 - 0xff) are reserved for Oveas.
 * 0xff is the OWL Identifier.
 */
define ('OWL_APPLICATION_PATTERN',	0xff000000);

/**
 * Bits 9-20 define the object type of an application
 */
define ('OWL_OBJECT_PATTERN',		0x00fff000);

/**
 * Bits 21-28 defines the (object specific) status code
 */
define ('OWL_STATUS_PATTERN',		0x00000ff0);

/**
 * Bits 29-32 define the severity
 */
define ('OWL_SEVERITY_PATTERN',		0x0000000f);

/**
 * @}
 */

/**
 * OWL keeps track of all running applications, their class and all status codes
 * their instances (objects) can have.
 * This is done in a global Register, which is maintained by this class.
 * \ingroup OWL_SO_LAYER
 */
abstract class Register
{
	/**
	 * Initialise the register array
	 * \public
	 */
	static public function init()
	{

		$_mtime = microtime(true);
		if (strpos($_mtime, '.') === false) {
			$_mtime .= '.0';
		}
		
		list ($_s, $_m) = explode ('.', $_mtime);
		$_s = sprintf ('%X', $_s);
		$_m = sprintf ('%X', $_m);

		$GLOBALS['register'] = array(
				  'run'				=> array(
				  							  'id'	=> "$_s$_m"
				  							, 'tcp' => ''
				  						)
				, 'applications'	=> array()
				, 'classes'			=> array()
				, 'severity'		=> array()
				, 'codes'			=> array()
				, 'code_symbols'	=> array()
				, 'stack'			=> array()
				);
		
	}
	/**
	 * Store the specified application in the register
	 * \public
	 * \param[in] $name Name of the class
	 * \param[in] $id Application ID
	 */
	static public function register_app ($name, $id)
	{
		if ($id == 0x00000000 || $id == 0xffffffff) {
			$_msg = sprintf("Access violation - ID for application %s (%%08X) is out of range",
					$name, $id);
			die ($_msg);
		}

		// use isset() here, since array_key_exists() gives a warning if the hex $id
		// has a negative integer value.
		// To make sure the ID is not interpreted as an index, cast it as a string
		if (!isset ($GLOBALS['register']['applications']["$id"])) {
			$GLOBALS['register']['applications']["$id"] = $name;
			$GLOBALS['register']['stack']['class'] = $id;
		}
		self::set_application ($id);
	}

	/**
	 * Store the specified class in the register, and setup an array to keep track of the codes
	 * \public
	 * \param[in] $name Name of the class
	 */
	static public function register_class ($name)
	{
// TODO Error handling when out of range

		$GLOBALS['register']['stack']['class'] += 0x00001000;
		$id = $GLOBALS['register']['stack']['class']; 

		// use isset() here, since array_key_exists() gives a warning if the hex $id
		// has a negative integer value.
		// To make sure the ID is not interpreted as an index, cast it as a string
		if (!isset ($GLOBALS['register']['classes']["$id"])) {
			$GLOBALS['register']['classes']["$id"] = $name;
			$GLOBALS['register']['codes']["$id"] = array();
		} else {
			// TODO; should we generate a warning here?
		}
	}

	/**
	 * Define a new statuscode
	 * \public
	 * \param[in] $code Symbolic name of the status code
	 */
	static public function register_code ($code)
	{
		if (defined ($code)) {
			// TODO; should we generate a warning here?
		}

		if (!array_key_exists ('severity', $GLOBALS['register']['stack'])) {
			die ("Fatal error - Register::register_code() called without a current severity; call Register::set_severity() first");
		}

		// Some pointers for readability and initialise non-existing arrays
		$_class = $GLOBALS['register']['stack']['class'];

		// Cast the $_class ID below to a string to make sure it's not interpreted as an index
		$_codes =& $GLOBALS['register']['codes']["$_class"];
		$_sev = $GLOBALS['register']['stack']['severity'];
	 
		if (!isset($_codes[$_sev])) {
			$_codes[$_sev] = 0x00000000;
//			echo "----&gt; New code: $_codes[$_sev]<br>";
		}
		$_codes[$_sev] += 0x00000010;
//			echo "----&gt; Increased code: $_codes[$_sev]<br>";

		$_value = $_class | $_codes[$_sev] | $_sev;
		define ($code, $_value);
		$GLOBALS['register']['code_symbols']["$_value"] = $code;
	}

	/**
	 * Store the known severitylevels in the register
	 * \public
	 * \param[in] $level Symbolic name for the severity level
	 * \param[in] $name Human readable value
	 */
	static public function register_severity ($level, $name)
	{
		$GLOBALS['register']['severity']['name']["$level"] = $name; // Cast as a string!
		$GLOBALS['register']['severity']['value']['OWL_' . $name] = $level;
	}

	/**
	 * Read a severity level from the register
	 * \public
	 * \param[in] $level Hex value of the severity level
	 * \return Human readable value
	 */
	static public function get_severity ($level)
	{
		if (!array_key_exists ("$level", $GLOBALS['register']['severity']['name'])) {
			return ('(unspecified)');
		} else {
			return ($GLOBALS['register']['severity']['name']["$level"]);
		}
	}

	/**
	 * This function is used by a config parse to translate a string value to
	 * the appropriate severity level
	 * \public
	 * \param[in] $name The name of the severity level
	 * \return Hex value of the severity level
	 */
	static public function get_severity_level ($name)
	{
		if (!array_key_exists ("$name", $GLOBALS['register']['severity']['value'])) {
			return (-1);
		} else {
			return ($GLOBALS['register']['severity']['value'][$name]);
		}
	}

	/**
	 * Return the ID of the current run
	 * \public
	 */
	static public function get_run_id ()
	{
		return ($GLOBALS['register']['run']['id']);
	}

	/**
	 * Translate an hex value code to the symbolic name
	 * \public
	 * \param[in] $value Hex value of the status code
	 * \param[in] $unknown Return value if the code does not exist
	 * \return Human readable value
	 */
	static public function get_code ($value, $unknown = '*unknown*')
	{
		if (!array_key_exists ("$value", $GLOBALS['register']['code_symbols'])) {
			return ($unknown);
		} else {
			return ($GLOBALS['register']['code_symbols']["$value"]);
		}
	}

	
	/**
	 * Point the register to the specified application.
	 * \public
	 * \param[in] $app_id Application ID
	 */
	static public function set_application ($app_id)
	{
		$GLOBALS['register']['stack']['app'] = $app_id;
	}

	/**
	 * Point the register to the specified class.
	 * \public
	 * \param[in] $class_id Class ID
	 */
	static public function set_class ($class_id)
	{
		$GLOBALS['register']['stack']['class'] = $class_id; 
	}

	/**
	 * Set the current severity to the specified level in the Register
	 * \public
	 * \param[in] $severity_level Severity level
	 */
	static public function set_severity ($severity_level)
	{
		$GLOBALS['register']['stack']['severity'] = $severity_level;
	}

	/**
	 * Load the message file for OWL and the application
	 * \param[in] $_force Boolean to force a reload with (different) translations, defaults to false
	 * \public
	 */
	static public function register_messages ($_force = false)
	{
		$_lang = ConfigHandler::get ('locale|lang');
		// Suppress 'Undefined constants' notices for codes not (yet) registered
		$_er = error_reporting(~E_NOTICE);
		if (!array_key_exists('owlMessages', $GLOBALS['OWLCache']['languageLoaded'])) {
			if (file_exists (OWL_LIBRARY . '/owl.messages.' . $_lang . '.php')) {
				require (OWL_LIBRARY . '/owl.messages.' . $_lang . '.php');
				$GLOBALS['OWLCache']['languageLoaded']['owlMessages'] = true;
			} elseif (file_exists (OWL_LIBRARY . '/owl.messages.php')) {
				require (OWL_LIBRARY . '/owl.messages.php');
				$GLOBALS['OWLCache']['languageLoaded']['owlMessages'] = true;
			} else {
				$GLOBALS['OWLCache']['languageLoaded']['owlMessages'] = false;
			}
			if ($GLOBALS['OWLCache']['languageLoaded']['owlMessages']) {
				$GLOBALS['messages'] = $_messages + $GLOBALS['messages'];
			}
		}
		if (!array_key_exists(strtolower(APPL_CODE) . 'Messages', $GLOBALS['OWLCache']['languageLoaded'])) {
			if (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.' . $_lang . '.php')) {
				require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.' . $_lang . '.php');
				$GLOBALS['OWLCache']['languageLoaded'][strtolower(APPL_CODE) . 'Messages'] = true;
			} elseif (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.php')){
				require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.php');
				$GLOBALS['OWLCache']['languageLoaded'][strtolower(APPL_CODE) . 'Messages'] = true;
			} else {
				$GLOBALS['OWLCache']['languageLoaded'][strtolower(APPL_CODE) . 'Messages'] = false;
			}
			if ($GLOBALS['OWLCache']['languageLoaded'][strtolower(APPL_CODE) . 'Messages']) {
				$GLOBALS['messages'] = $_messages + $GLOBALS['messages'];
			}
		}
		error_reporting($_er);
	}

	/**
	 * Load the labels file for OWL or the application
	 * \param[in] $_owl When true, the OWL file(s) will be loaded, by default only the application's
	 * \public
	 */
	static public function register_labels ($_owl = false)
	{
		$_lang = ConfigHandler::get ('locale|lang');
		// Suppress 'Undefined constants' notices for codes not (yet) registered
		if ($_owl) {
			if (!array_key_exists('owlLabels', $GLOBALS['OWLCache']['labelsLoaded'])) {
				if (file_exists (OWL_LIBRARY . '/owl.labels.' . $_lang . '.php')) {
					require (OWL_LIBRARY . '/owl.labels.' . $_lang . '.php');
					$GLOBALS['OWLCache']['labelsLoaded']['owlLabels'] = true;
				} elseif (file_exists (OWL_LIBRARY . '/owl.labels.php')) {
					require (OWL_LIBRARY . '/owl.labels.php');
					$GLOBALS['OWLCache']['labelsLoaded']['owlLabels'] = true;
				} else {
					$GLOBALS['OWLCache']['labelsLoaded']['owlLabels'] = false;
				}
				if ($GLOBALS['OWLCache']['labelsLoaded']['owlLabels']) {
					$GLOBALS['labels'] = $_labels + $GLOBALS['labels'];
				}
			}
		} else {
			if (!array_key_exists(strtolower(APPL_CODE) . 'Labels', $GLOBALS['OWLCache']['labelsLoaded'])) {
				if (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.' . $_lang . '.php')) {
					require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.' . $_lang . '.php');
					$GLOBALS['OWLCache']['labelsLoaded'][strtolower(APPL_CODE) . 'Labels'] = true;
				} elseif (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.php')) {
					require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.php');
					$GLOBALS['OWLCache']['labelsLoaded'][strtolower(APPL_CODE) . 'Labels'] = true;
				} else {
					$GLOBALS['OWLCache']['labelsLoaded'][strtolower(APPL_CODE) . 'Labels'] = false;
				}
				if ($GLOBALS['OWLCache']['labelsLoaded'][strtolower(APPL_CODE) . 'Labels']) {
					$GLOBALS['labels'] = $_labels + $GLOBALS['labels'];
				}
			}
		}
	}
}

Register::init();