<?php
/**
 * \file
 * Define a class for config handling
 * \version $Id: class.confighandler.php,v 1.11 2011-04-19 13:00:03 oscar Exp $
 */

/**
 * \ingroup OWL_SO_LAYER
 * This abstract class reads configution from file ort database, and fills and
 * reads the global datastructure with config items
 * \brief Configuration handler 
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Aug 20, 2008 -- O van Eijk -- initial version
 */
abstract class ConfigHandler
{

	/**
	 * Datahandler object for database access
	 */	
	private static $dataset = null;

	/**
	 * Parse the given configuration source
	 * \public
	 * \param[in] $_source Array describing the configuration source. It can have the following keys:
	 *  - file: Full path to the configuration file
	 *  - table: Config tablename without prefix. Default 'config', MUST be given with the first call
	 *  - applic: Application name for which the config should be read, default 'owl'
	 *  - group: Application name for which the config should be read, default 0
	 *  - user: Application name for which the config should be read, default 0
	 *  - force: Boolean that can force overwrite of protected values, default false
	 * 
	 * The first call must always read from a file. On subsequent calls, if no filename is given,
	 * the configuration is taken from the (owl_)config table
	 */
	public static function read_config (array $_source)
	{
		if (array_key_exists('file', $_source)) {
			self::config_file($_source['file']);
		} else {
			self::config_table(
				 (array_key_exists('table', $_source) ? $_source['table'] : 'config')
				,(array_key_exists('aid', $_source) ? $_source['aid'] : OWL_APPL_ID)
				,(array_key_exists('group', $_source) ? $_source['group'] : 0)
				,(array_key_exists('user', $_source) ? $_source['user'] : 0)
				,(array_key_exists('force', $_source) ? toStrictBoolean($_source['force']) : false)
			);
		}
	}

	/**
	 * Parse a configuration file
	 * \param[in] $_file Filename
	 */
	private static function config_file ($_file)
	{
		if (($fpointer = fopen ($_file, 'r')) === false) {
			die ('Fatal error reading configuration file: ' . $_file);
		}
		while (!feof($fpointer)) {
			$_line = fgets ($fpointer, 8192);
			$_line = preg_replace ('/^\s*;.*/', '', $_line);
			$_line = trim ($_line);
			if ($_line == '') {
				continue;
			}

			list ($_item, $_value) = explode ('=', $_line, 2);
			$_item = trim ($_item);
			if ($_item == '') {
				continue;
			}
			$_value = trim ($_value);

			$_protect = strpos ($_item, $GLOBALS['config']['config']['protect_tag']);
			$_protect = ($_protect !== false);

			$_hide = strpos ($_item, $GLOBALS['config']['config']['hide_tag']);
			$_hide = ($_hide !== false);

			self::parse_item($_item, $_value, $_protect, $_hide);
		}
		fclose ($fpointer);
	}

	/**
	 * Parse a configuration table
	 * \param[in] $_table Config tablename without prefix
	 * \param[in] $_applic Application name for which the config should be read
	 * \param[in] $_group Group ID for which the config should be read
	 * \param[in] $_user User ID for which the config should be read
	 * \param[in] $_force Boolean that can force overwrite of protected values
	 */
	private static function config_table ($_table, $_applic, $_group, $_user, $_force)
	{
		if (self::$dataset === null) {
			self::$dataset = new DataHandler();
			if (self::get ('owltables', true)) {
				self::$dataset->set_prefix(self::get ('owlprefix'));
			}
			self::$dataset->set_tablename($_table);
		}
		self::$dataset->set('aid', $_applic);
		self::$dataset->set('gid', $_group);
		self::$dataset->set('uid', $_user);
		self::$dataset->prepare ();
		$_cfg = null;
		self::$dataset->db ($_cfg, __LINE__, __FILE__);
		if (count($_cfg) > 0) {
			foreach ($_cfg as $_item) {
				self::parse_item($_item['name'], $_item['value'], $_item['protect'], $_item['hide']);
			}
		}
	}

	/**
	 * Convert values in character string format to a known value
	 * \private
	 * \param[in] $val The value as read from the config file
	 * \return Value in the desired format (or as is if nothing set)
	 */
	private static function convert ($val)
	{
		if (($_s = Register::get_severity_level($val)) > 0) {
			return ($_s);
		}
		// TODO; We've got toStrictBoolean() for this now
		if ($val === 'true' || $val === 'True' || $val === 'TRUE' || $val === '1') {
			return (true);
		}
		if ($val === 'false' || $val === 'False' || $val === 'FALSE' || $val === '0') {
			return (false);
		}
		return ($val);
	}

	/**
	 * Parse a configuration item as read from the file or database, and store is
	 * \param[in] $_item Name of the config item
	 * \param[in] $_value Value of the config item
	 * \param[in] $_protect Boolean indicating a protected value
	 * \param[in] $_hide Boolean indicated a hidden value
	 */
	private static function parse_item ($_item, $_value, $_protect, $_hide)
	{
		if ($_protect === true) {
			$_item = str_replace($GLOBALS['config']['config']['protect_tag'], '', $_item);
			$GLOBALS['config']['protected_values'][] = $_item;
		}
		if (in_array($_item, $GLOBALS['config']['protected_values'])
			&& array_key_exists($_item, $GLOBALS['config']['values'])) {
			OWL::stat(CONFIG_PROTECTED, $_item);
			return;
		}

		if ($_hide === true) {
			$_item = str_replace($GLOBALS['config']['config']['hide_tag'], '', $_item);
		}
		$_value = self::convert ($_value);
		self::_set($_item, $_value, $_hide);
	}

	/**
	 * Return a configuration value.
	 * Note! In order to use hidden values properly, this is the ONLY way configuration
	 * values should be retrieved!
	 * \public
	 * \param[in] $item The configuration item in the same format as it appears in the
	 * configuration file (e.g. 'group|subject|item')
	 * \param[in] $default The default value to return if the config item was not set. This defaults
	 * to 'null'; if it is anything other than null, the CONFIG_NOVALUE status will not be set
	 * \param[in] $force Boolean to force a reparse of the config item ignoring existing cache values
	 * \return Corresponding value of null when nothing was found
	 */
	public static function get ($item, $default = null, $force = false)
	{
		if ($force === false && isset ($GLOBALS['owl_cache']['cget'][$item])) {
			return ($GLOBALS['owl_cache']['cget'][$item]);
		}

		$_cache =& $GLOBALS['owl_cache']['cget'][$item];
		$_c =& $GLOBALS['config']['values'];
		$_h =& $GLOBALS['config']['hidden_values'];
		
		if (strpos ($item, '|') !== false) {
			$item = explode ('|', $item);
			foreach ($item as $_k => $_v) {
				$_c =& $_c[$_v];
				if (array_key_exists($_v, $_h)) {
					$_h =& $_h[$_v];
				}
			}
		} else {
			$_c =& $_c[$item];
			if (array_key_exists($item, $_h)) {
				$_h =& $_h[$item];
			}
		}

		if (!isset ($_c)) {
			if ($default === null) {
				OWL::stat (CONFIG_NOVALUE, (is_array($item)?implode('|', $item):$item)); 
				return (null);
			} else {
				return $default;
			}
		}
		if ($_c === $GLOBALS['config']['config']['hide_value']) {
			$_cache = owlCrypt($_h);
		} else {
			$_cache = $_c;
		}
		return ($_cache);
	}


	/**
	 * Set a configuration item. Existing values will be overwritten when not protected.
	 * \public
	 * \param[in] $_item The configuration item in the same format as it appears in the
	 * configuration file (e.g. 'group|subject|item')
	 * \param[in] $_value The new value of the item
	 */
	public static function set ($_item, $_value)
	{
		if (isset ($GLOBALS['owl_cache']['cget'][$_item])) {
			// Clean the cache
			unset ($GLOBALS['owl_cache']['cget'][$_item]);
		}

		if (in_array($_item, $GLOBALS['config']['protected_values'])) {
			OWL::stat(CONFIG_PROTECTED, $_item);
			return;
		}
		self::_set($_item, $_value, array_key_exists($_item, $GLOBALS['config']['hidden_values']));
		
	}

	/**
	 * Set or update a configuration item
	 * \param[in] $_item Item name or path (seperated with '|')
	 * \param[in] $_value The calue to be set
	 * \param[in] $_hide Boolean which it true when this is a hidden item
	 */
	private static function _set ($_item, $_value, $_hide)
	{
		if ($_hide) {
			$_value = owlCrypt($_value);
		}
		
		if (strpos ($_item, '|') !== false) {
			$_item = explode ('|', $_item);
			$_pointer =& $GLOBALS['config']['values'];
			if ($_hide) {
				$_hidden =& $GLOBALS['config']['hidden_values'];
			}
			foreach ($_item as $_k => $_v) {
				if ($_k == (count ($_item)-1)) {
					if ($_hide) {
						$_hidden[$_v] = $_value; 
						$_pointer[$_v] = $GLOBALS['config']['config']['hide_value'];
					} else {
						$_pointer[$_v] = $_value;
					}
				} else {
					if (!array_key_exists($_v, $_pointer)) {
						$_pointer[$_v] = array();
					}
					$_pointer =& $_pointer[$_v];
					if ($_hide) {
						if (!array_key_exists($_v, $_hidden)) {
							$_hidden[$_v] = array();
						}
						$_hidden =& $_hidden[$_v];
					}
				}
			}
		} else {
			if ($_hide) {
				$GLOBALS['config']['hidden_values'][$_item] = $_value;
				$GLOBALS['config']['values'][$_item] = $GLOBALS['config']['config']['hide_value'];
			} else {
				$GLOBALS['config']['values'][$_item] = $_value;
			}
		}
	}
}

/*
 * Register this class and all status codes
 */

Register::register_class ('ConfigHandler');

//Register::set_severity (OWL_DEBUG);
Register::set_severity (OWL_INFO);
Register::register_code ('CONFIG_PROTECTED');

//Register::set_severity (OWL_OK);
//Register::set_severity (OWL_SUCCESS);
//Register::set_severity (OWL_WARNING);

//Register::set_severity (OWL_BUG);

Register::set_severity (OWL_ERROR);
Register::register_code ('CONFIG_NOVALUE');

//Register::set_severity (OWL_FATAL);
//Register::set_severity (OWL_CRITICAL);
