<?php
/**
 * \file
 * Define the abstract Register class.
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \copyright{2007-2011} Oscar van Eijk, Oveas Functionality Provider
 * \license
 * This file is part of OWL-PHP.
 *
 * OWL-PHP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * OWL-PHP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OWL-PHP. If not, see http://www.gnu.org/licenses/.
 */


/**
 * \defgroup OWL_Bitmaps Status code bitmaps
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
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version May 15, 2007 -- O van Eijk -- initial version
 */
abstract class Register
{
	/**
	 * Initialise the register array
	 * \author Oscar van Eijk, Oveas Functionality Provider
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
	 * \param[in] $name Name of the class
	 * \param[in] $id Application ID. This is an 8 byte code: 0xaabbbbbb, where aa is a developer code and bbbbbb is
	 * the developer's application index. Developer code 0xff is reserved for Oveas.
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function registerApp ($name, $id)
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
		self::setApplication ($id);
	}

	/**
	 * Store the specified class in the register, and setup an array to keep track of the codes
	 * \param[in] $name Name of the class
	 * \todo Error handling when out of range
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function registerClass ($name)
	{
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
	 * \param[in] $code Symbolic name of the status code
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function registerCode ($code)
	{
		if (defined ($code)) {
			// TODO; should we generate a warning here?
		}

		if (!array_key_exists ('severity', $GLOBALS['register']['stack'])) {
			die ("Fatal error - Register::registerCode() called without a current severity; call Register::setSeverity() first");
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
	 * \param[in] $level Symbolic name for the severity level
	 * \param[in] $name Human readable value
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function registerSeverity ($level, $name)
	{
		$GLOBALS['register']['severity']['name']["$level"] = $name; // Cast as a string!
		$GLOBALS['register']['severity']['value']['OWL_' . $name] = $level;
	}

	/**
	 * Read a severity level from the register
	 * \param[in] $level Hex value of the severity level
	 * \return Human readable value
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function getSeverity ($level)
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
	 * \param[in] $name The name of the severity level
	 * \return Hex value of the severity level
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function getSeverityLevel ($name)
	{
		if (!array_key_exists ("$name", $GLOBALS['register']['severity']['value'])) {
			return (-1);
		} else {
			return ($GLOBALS['register']['severity']['value'][$name]);
		}
	}

	/**
	 * Return the ID of the current run
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function getRunId ()
	{
		return ($GLOBALS['register']['run']['id']);
	}

	/**
	 * Translate an hex value code to the symbolic name
	 * \param[in] $value Hex value of the status code
	 * \param[in] $unknown Return value if the code does not exist
	 * \return Human readable value
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function getCode ($value, $unknown = '*unknown*')
	{
		if (!array_key_exists ("$value", $GLOBALS['register']['code_symbols'])) {
			return ($unknown);
		} else {
			return ($GLOBALS['register']['code_symbols']["$value"]);
		}
	}


	/**
	 * Point the register to the specified application.
	 * \param[in] $app_id Application ID
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function setApplication ($app_id)
	{
		$GLOBALS['register']['stack']['app'] = $app_id;
	}

	/**
	 * Point the register to the specified class.
	 * \param[in] $class_id Class ID
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function setClass ($class_id)
	{
		$GLOBALS['register']['stack']['class'] = $class_id;
	}

	/**
	 * Set the current severity to the specified level in the Register
	 * \param[in] $severity_level Severity level
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function setSeverity ($severity_level)
	{
		$GLOBALS['register']['stack']['severity'] = $severity_level;
	}

	/**
	 * Load the message file for OWL and the application
	 * \param[in] $_force Boolean to force a reload with (different) translations, defaults to false
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function registerMessages ($_force = false)
	{
		$_lang = ConfigHandler::get ('locale', 'lang');
		// Suppress 'Undefined constants' notices for codes not (yet) registered
		$_er = error_reporting(~E_NOTICE);
		if (OWLCache::get(OWLCACHE_MESSAGE, 'owlMessages') === null) {
			if (file_exists (OWL_LIBRARY . '/owl.messages.' . $_lang . '.php')) {
				require (OWL_LIBRARY . '/owl.messages.' . $_lang . '.php');
				$_found = OWLCache::set(OWLCACHE_MESSAGE, 'owlMessages', true);
			} elseif (file_exists (OWL_LIBRARY . '/owl.messages.php')) {
				require (OWL_LIBRARY . '/owl.messages.php');
				$_found = OWLCache::set(OWLCACHE_MESSAGE, 'owlMessages', true);
			} else {
				$_found = OWLCache::set(OWLCACHE_MESSAGE, 'owlMessages', false);
			}
			if ($_found === true) {
				$GLOBALS['messages'] = $_messages + $GLOBALS['messages'];
			}
		}

		if (OWLCache::get(OWLCACHE_MESSAGE, strtolower(APPL_CODE) . 'Messages') === null) {
			if (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.' . $_lang . '.php')) {
				require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.' . $_lang . '.php');
				$_found = OWLCache::set(OWLCACHE_MESSAGE, strtolower(APPL_CODE) . 'Messages', true);
			} elseif (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.php')){
				require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.messages.php');
				$_found = OWLCache::set(OWLCACHE_MESSAGE, strtolower(APPL_CODE) . 'Messages', true);
			} else {
				$_found = OWLCache::set(OWLCACHE_MESSAGE, strtolower(APPL_CODE) . 'Messages', false);
			}
			if ($_found === true) {
				$GLOBALS['messages'] = $_messages + $GLOBALS['messages'];
			}
		}
		error_reporting($_er);
	}

	/**
	 * Load the labels file for OWL or the application
	 * \param[in] $_owl When true, the OWL file(s) will be loaded, by default only the application's
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function registerLabels ($_owl = false)
	{
		$_lang = ConfigHandler::get ('locale', 'lang');
		// Suppress 'Undefined constants' notices for codes not (yet) registered
		if ($_owl) {
			if (OWLCache::get(OWLCACHE_LABELS, 'owlLabels') === null) {
				if (file_exists (OWL_LIBRARY . '/owl.labels.' . $_lang . '.php')) {
					require (OWL_LIBRARY . '/owl.labels.' . $_lang . '.php');
					$_found = OWLCache::set(OWLCACHE_LABELS, 'owlLabels', true);
				} elseif (file_exists (OWL_LIBRARY . '/owl.labels.php')) {
					require (OWL_LIBRARY . '/owl.labels.php');
					$_found = OWLCache::set(OWLCACHE_LABELS, 'owlLabels', true);
				} else {
					$_found = OWLCache::set(OWLCACHE_LABELS, 'owlLabels', false);
				}
				if ($_found === true) {
					$GLOBALS['labels'] = $_labels + $GLOBALS['labels'];
				}
			}
		} else {
			if (OWLCache::get(OWLCACHE_LABELS, strtolower(APPL_CODE) . 'Labels') === null) {
				if (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.' . $_lang . '.php')) {
					require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.' . $_lang . '.php');
					$_found = OWLCache::set(OWLCACHE_LABELS, strtolower(APPL_CODE) . 'Labels', true);
				} elseif (file_exists (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.php')) {
					require (APPL_LIBRARY . '/' . strtolower(APPL_CODE) . '.labels.php');
					$_found = OWLCache::set(OWLCACHE_LABELS, strtolower(APPL_CODE) . 'Labels', true);
				} else {
					$_found = OWLCache::set(OWLCACHE_LABELS, strtolower(APPL_CODE) . 'Labels', false);
				}
				if ($_found === true) {
					$GLOBALS['labels'] = $_labels + $GLOBALS['labels'];
				}
			}
		}
	}
}

Register::init();
