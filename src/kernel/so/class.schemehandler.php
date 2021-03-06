<?php
/**
 * \file
 * This file defines the Scheme Handler class
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \copyright{2007-2011} Oscar van Eijk, Oveas Functionality Provider
 * \license
 * This file is part of Terra-Terra.
 *
 * Terra-Terra is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Terra-Terra is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Terra-Terra. If not, see http://www.gnu.org/licenses/.
 */

/**
 * \ingroup TT_SO_LAYER
 * Handler for all database schemes. This singleton class handles all updates to db tables
 * \brief Scheme handler
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Oct 7, 2010 -- O van Eijk -- initial version for TT
 * \note A port of this class has been created for VirtueMart
 */
class SchemeHandler extends _TT
{
	/**
	 * integer - Scheme Handle ID
	 */
	private $id;

	/**
	 * integer - Reference to the database class
	 */
	private $db;

	/**
	 * Array - table description
	 */
	private $scheme;

	/**
	 * Array - table name
	 */
	private $table = '';

	/**
	 * string - Optional engine name.
	 */
	private $engine = null;

	/**
	 * integer - self reference
	 */
	private static $instance;

	/**
	 * boolean - True when a scheme is filled with data and not yet used
	 */
	private $inuse = false;

	/**
	 * Class constructor
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function __construct ()
	{
		_TT::init(__FILE__, __LINE__);
		$this->db = DbHandler::getInstance();
		$this->setStatus (__FILE__, __LINE__, TT_STATUS_OK);
	}

	/**
	 * Class destructor
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function __destruct ()
	{
		if (parent::__destruct() === false) {
			return;
		}
	}

	/**
	 * Implementation of the __clone() function to prevent cloning of this singleton;
	 * it triggers a fatal (user)error
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function __clone ()
	{
		trigger_error('invalid object cloning');
	}

	/**
	 * Return a reference to my implementation. If necessary, create that implementation first.
	 * \return Severity level
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public static function getInstance()
	{
		if (!SchemeHandler::$instance instanceof self) {
			SchemeHandler::$instance = new self();
		}
		return SchemeHandler::$instance;
	}

	/**
	 * Set a new tablename
	 * \param[in] $_tblname Name of the table to create, check or modify
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function createScheme ($_tblname)
	{
		if ($this->inuse) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_INUSE, $this->table);
			return ($this->severity);
		}
		self::reset();
		$this->table = $_tblname;
		$this->inuse = true;
	}

	/**
	 * Define the engine for this scheme.
	 * \note The driver being used must support this
	 * \param[in] $_engine Specification of the engine
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function setEngine ($_engine)
	{
		if (!$this->inuse) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOTINUSE);
			return ($this->severity);
		}
		$this->engine = $_engine;
	}
	
	/**
	 * Define the layout for a table
	 * \param[in] $_scheme Array holding the table description. This is a 2 dimensional array where the
	 * first level holds the fieldnames. The second array defines the attributes for each field:
	 * - type : String; the field-type (INT|TINYINT|DECIMAL|VARCHAR|MEDIUMTEXT|TEXT|LONGTEXT|BLOB|LONGBLOB|ENUM|SET)
	 * - length : Integer; indicating the length for fieldtypes that use that (like INT and VARCHAR)
	 * - precision : Integer; indicating the precision for floating point values
	 * - null : Boolean; when true the value can be NULL
	 * - auto-inc : Boolean; True for auto-increment values (will be set as primary key)
	 * - default : Mixed; default value
	 * - options : Array; for SET and ENUM types. the list of possible values
	 * - unsigned : Boolean; True for UNSIGNED numeric values
	 * - zerofill : Boolean; True when numberic values should be represented with leading zeros
	 * - comment : String; field comment
	 *
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function defineScheme($_scheme)
	{
		if (!$this->inuse) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOTINUSE);
			return ($this->severity);
		}
		$this->scheme['columns'] = $_scheme;
		return $this->validateScheme();
	}

	/**
	 * Define the indexes for a table
	 * \param[in] $_index Array holding the index description. This is a 2 dimensional array where the
	 * first level holds the indexname. The second array defines the attributes for each index:
	 * - unique : Boolean; True for unique keys
	 * - primary : Boolean; True for the primary key
	 * - columns : Array; List with columnnames that will be indexed
	 * - type : String (optional); Index type, currenty only supports 'FULLTEXT'
	 * \return Severity level
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function defineIndex($_index)
	{
		if (!$this->inuse) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOTINUSE);
			return ($this->severity);
		}
		$_primary = false;
		foreach ($_index as $_name => $_descr) {
			if (array_key_exists('primary', $_descr) && $_descr['primary']) {
				if ($_primary) {
					$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_DUPLPRKEY, $this->table);
					return false;
				}
				$_name = 'PRIMARY';
				$_primary = true;
			}
			unset ($_descr['primary']);
			$this->scheme['indexes'][$_name] = $_descr;
		}
		return $this->validateScheme();
	}

	/**
	 * If the table does not exist, or differs from the defined scheme, create of modify the table
	 * \param[in] $_drops True if existing fields should be dropped; default false.
	 * If existing fields should be converted to new fields, call with DbScheme::scheme(false) first,
	 * then do the conversions, next call DbScheme::scheme(true).
	 * \return Severity level
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	function scheme($_drops = false)
	{
		if (!$this->inuse) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOTINUSE);
			return ($this->severity);
		}
		$_return = $this->compare();
		if ($_return === true) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_EXISTS, $this->table);
			return ($this->severity);
		} elseif ($_return === false) {
			$_stat = $this->createTable(); // table does not exist
		} else {
			$_stat = $this->alterTable($_return, $_drops); // differences found
		}
		if (!$_stat) {
			$_db = $this->db->getResource(); // Create a variable since it's passed by reference
			$this->db->getDriver()->dbError($_db, $_nr, $_msg);
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_DBERROR, $_msg);
		}
		return ($this->severity);
	}

	/**
	 * Modify 1 or more fields in an existing scheme definition
	 * \param[in] $_field Array holding 1 or more field descriptions
	 * \see defineScheme()
	 * \return Boolean; false if the table description contains errors
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function alterScheme($_field)
	{
		if (!$this->inuse) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOTINUSE);
			return ($this->severity);
		}
		foreach ($_field as $_fieldname => $_attributes) {
			$this->scheme['columns'][$_fieldname][$_attributes[0]] = $_attributes[1];
		}
		return $this->validateScheme();
	}

	/**
	 * Validate the defined scheme. Some values will be modified to make sure the SQL
	 * statements can be prepared and compare() won't find differences on case diffs
	 * \return boolean False if there is an error in the scheme definition, True if no errors were found
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function validateScheme()
	{
		$_counters = array(
			 'auto_inc' => 0
		);
		if (!array_key_exists('columns', $this->scheme) || count($this->scheme['columns']) == 0) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOCOLS, $this->table);
			return false;
		}
		foreach ($this->scheme['columns'] as $_fld => $_desc) {
			$this->scheme['columns'][$_fld]['type'] = strtolower($_desc['type']);
			if (array_key_exists('auto_inc',$_desc) && $_desc['auto_inc'] == true) {
				$this->scheme['indexes']['PRIMARY'] = array(
							 'columns' => array ($_fld)
							,'primary' => true
							,'unique' => true
							,'type' => null
				);
				if ($_counters['auto_inc'] > 0) {
				$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_MULAUTOINC, $this->table);
					return false;
				}
				$_counters['auto_inc']++;
			}
			if (array_key_exists('length',$_desc) && $_desc['length'] == 0) {
				unset ($this->scheme['columns'][$_fld]['length']);
			}
			if (array_key_exists('options',$_desc)) {
				for ($_idx = 0; $_idx < count($_desc['options']); $_idx++) {
					if (preg_match("/^'.*'$/", $_desc['options'][$_idx]) == 0) {
						$this->scheme['columns'][$_fld]['options'][$_idx] = "'" . $_desc['options'][$_idx] . "'";
					}
				}
			}

		}
		if (!array_key_exists('indexes', $this->scheme) || count($this->scheme['indexes']) == 0) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOINDEX, $this->table);
			return true;
		}
		foreach ($this->scheme['indexes'] as $_idx => $_desc) {
			if (!array_key_exists('columns', $_desc)
				|| !is_array($_desc['columns'])
				|| count($_desc['columns']) == 0) {
					$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOCOLIDX, $this->table, $_idx);
					return false;
			}
			foreach ($_desc['columns'] as $_fld) {
				if (!array_key_exists($_fld, $this->scheme['columns'])) {
					$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_IVCOLIDX, $this->table, $_idx, $_fld);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Compare the scheme with an existing database table
	 * \return mixed True if there are no differences, False if the table does not exist, or an
	 * array with differences
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function compare ()
	{
		$_diffs = array();
		$_current = array();
		$this->tableDescription($this->table, $_current);
		if ($this->getStatus() === SCHEMEHANDLE_NOTABLE) {
			return false;
		}
		foreach ($this->scheme['columns'] as $_fld => $_descr) {
			if (!array_key_exists($_fld, $_current['columns'])) {
				$_diffs['add']['columns'][$_fld] = $_descr;
			} else {
				foreach ($_descr as $_item => $_value) {
					if (!array_key_exists($_item, $_current['columns'][$_fld])
							|| ($_value != $_current['columns'][$_fld][$_item])) {
						$_diffs['mod']['columns'][$_fld] = $_descr;
					}
				}
			}
		}
		foreach ($_current['columns'] as $_fld => $_descr) {
			if (!array_key_exists($_fld, $this->scheme['columns'])) {
				$_diffs['drop']['columns'][$_fld] = $_descr;
			}
		}
		if (count($_diffs) == 0) {
			return true;
		}
		return $_diffs;
	}

	/**
	 * Create the defined table
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function createTable()
	{

		$_colDefs = array();
		$_idxDefs = array();
		foreach ($this->scheme['columns'] as $_fld => $_desc) {
			$_q = $this->db->getDriver()->dbDefineField($this->db->tablename($this->table, true), $_fld, $_desc);
			if ($_q !== null) {
				$_colDefs[] = $_q;
			}
		}
		foreach ($this->scheme['indexes'] as $_idx => $_desc) {
			$_q = $this->db->getDriver()->dbDefineIndex($this->db->tablename($this->table, true), $_idx, $_desc);
			if ($_q !== null) {
				$_idxDefs[] = $_q;
			}
		}
		$_db = $this->db->getResource(); // Create a variable since it's passed by reference
		return ($this->db->getDriver()->dbCreateTable($_db, $this->db->tablename($this->table), $_colDefs, $_idxDefs, $this->engine));
	}

	/**
	 * Make changes to the table
	 * \param[in] $_diffs Changes to make
	 * \param[in] $_drops True if existing fields should be dropped
	 * \return True on success
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 * \todo When altering a field, Oracle complains when an item is already as requested
	 * (eg, VARCHAR2(24) NOT NULL -> VARCHAR2(48) NOT NULL results in a warning).
	 * Items that should be changed are changes, but to suppress the warning, a check must be made
	 * to make sure onl;y actual changes are in the ALTER statement.
	 */
	private function alterTable($_diffs, $_drops)
	{
		$_db = $this->db->getResource(); // Create a variable since it's passed by reference
		if ($_drops === true && array_key_exists('drop', $_diffs) && count($_diffs['drop']['columns']) > 0) {
			foreach ($_diffs['drop']['columns'] as $_fld => $_desc) {
				if (!$this->db->getDriver()->dbDropField($_db, $this->db->tablename($this->table, true), $_fld)) {
					return (false);
				}
			}
		}
		if (array_key_exists('mod', $_diffs) && count($_diffs['mod']['columns']) > 0) {
			foreach ($_diffs['mod']['columns'] as $_fld => $_desc) {
				if (!$this->db->getDriver()->dbAlterField($_db, $this->db->tablename($this->table, true), $_fld, $_desc)) {
					return (false);
				}
			}
		}
		if (array_key_exists('add', $_diffs) && count($_diffs['add']['columns']) > 0) {
			foreach ($_diffs['add']['columns'] as $_fld => $_desc) {
				if (!$this->db->getDriver()->dbAddField($_db, $this->db->tablename($this->table, true), $_fld, $_desc)) {
					return (false);
				}
			}
		}
		return true;
	}


	/**
	 * Get the columns for a given table
	 * \param[in] $_tablename The tablename
	 * \return Indexed array holding all fields =&gt; datatypes, or null on errors
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function getTableColumns($_tablename)
	{
		$_columns = $this->db->getDriver()->dbTableColumns($this->db, $this->db->tablename($_tablename, true));
		if ($_columns === null) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_EMPTYTABLE, $_tablename);
			return null;
		}
		return $_columns;
	}

	/**
	 * Get the indexes for a given table
	 * \param[in] $_tablename The tablename
	 * \return Indexed array holding all fields =&gt; datatypes, or null on errors
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function getTableIndexes($_tablename)
	{
		$_indexes = $this->db->getDriver()->dbTableIndexes($this->db, $this->db->tablename($_tablename, true));
		if ($_indexes === null) {
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOINDEX, $_tablename);
			return null;
		}
		return $_indexes;
	}

	/**
	 * Get a description of a database table
	 * \param[in] $tablename The tablename
	 * \param[out] $data Indexed array holding all fields =&gt; datatypes
	 * \return Severity level
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function tableDescription ($tablename, &$data)
	{
		if (!$this->db->tableExists($tablename)) {
			$data = array();
			$this->setStatus (__FILE__, __LINE__, SCHEMEHANDLE_NOTABLE, $tablename);
			return ($this->severity);
		}
		$data['columns'] = $this->getTableColumns($tablename);
		$data['indexes'] = $this->getTableIndexes($tablename);
		return ($this->severity);
	}

	/**
	 * Reset the internal data structure
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function reset()
	{
		$this->scheme = array();
		$this->table = '';
		$this->inuse = false;
		parent::reset();
	}
}
/**
 * \example exa.schemehandler.php
 * This example shows how to create or alter a table using the SchemeHandler class
 * \author Oscar van Eijk, Oveas Functionality Provider
 */

/*
 * Register this class and all status codes
 */
Register::registerClass ('SchemeHandler', TT_APPNAME);

//Register::setSeverity (TT_DEBUG);

Register::setSeverity (TT_INFO);
Register::registerCode ('SCHEMEHANDLE_NOTABLE');
Register::registerCode ('SCHEMEHANDLE_NOINDEX');
Register::registerCode ('SCHEMEHANDLE_EXISTS');

//Register::setSeverity (TT_OK);
//Register::setSeverity (TT_SUCCESS);

Register::setSeverity (TT_WARNING);
Register::registerCode ('SCHEMEHANDLE_IVTABLE');

Register::setSeverity (TT_BUG);
Register::registerCode ('SCHEMEHANDLE_INUSE');
Register::registerCode ('SCHEMEHANDLE_NOTINUSE');
Register::registerCode ('SCHEMEHANDLE_EMPTYTABLE');

Register::setSeverity (TT_ERROR);
Register::registerCode ('SCHEMEHANDLE_DBERROR');
Register::registerCode ('SCHEMEHANDLE_DUPLPRKEY');
Register::registerCode ('SCHEMEHANDLE_MULAUTOINC');
Register::registerCode ('SCHEMEHANDLE_NOCOLIDX');
Register::registerCode ('SCHEMEHANDLE_IVCOLIDX');
Register::registerCode ('SCHEMEHANDLE_NOCOLS');

Register::setSeverity (TT_FATAL);
//Register::setSeverity (TT_CRITICAL);
