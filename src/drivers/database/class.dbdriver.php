<?php
/**
 * \file
 * This file defines the Database drivers
 * \version $Id: class.dbdriver.php,v 1.2 2011-04-19 13:00:03 oscar Exp $
 */

/**
 * \ingroup OWL_DRIVERS
 * Interface that defines the database drivers. Some of the methods are implemented in class DbDefaults
 * \brief Database driver interface
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Apr 12, 2011 -- O van Eijk -- initial version
 */
interface DbDriver
{
	/**
	 * Conctructor.
	 */
	 public function __construct();

	/**
	 * Create a new database
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_name Name of the new database
	 * \return a negative value on failures, any other integer on success
	 */
	 public function dbCreate (&$_resource, $_name);

	/**
	 * Get the last error number and error text from the database server
	 * \param[in] $_resource Link with the database server
	 * \param[out] $_number Error number
	 * \param[out] $_text Error text
	 */
	 public function dbError (&$_resource, &$_number, &$_text);

	
	/**
	 * Make a connection with a database server
	 * \param[out] $_resource Link with the database server
	 * \param[in] $_server Server to connect to
	 * \param[in] $_name Database name to open
	 * \param[in] $_user Username to connect with
	 * \param[in] $_password Password to connect with
	 * \param[in] $_multiple True when multiple connections are allowed, default is false
	 * \return True on success, false on failures
	 */
	 public function dbConnect (&$_resource, $_server, $_name, $_user, $_password, $_multiple = false);

	/**
	 * Open a database
	 * \param[in,out] $_resource Link with the database server
	 * \param[in] $_server Server to connect to
	 * \param[in] $_name Database name to open
	 * \param[in] $_user Username to connect with
	 * \param[in] $_password Password to connect with
	 * \return True on success, false on failures
	 */
	 public function dbOpen (&$_resource, $_server, $_name, $_user, $_password);

	/**
	 * Get a list with tablenames 
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_pattern Search pattern
	 * \param[in] $_views True when views should be included. Default is false
	 * \return Indexed array with matching tables and their attributes
	 */
	 public function dbTableList (&$_resource, $_pattern, $_views = false);

	/**
	 * Read from the database
	 * \param[out] $_data Dataset retrieved by the given query
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_query Query to execute
	 * \return True on success, false on failures
	 */
	 public function dbRead (&$_data, &$_resource, $_query);

	/**
	 * Write to the database
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_query Query to execute
	 * \return Number of affected rows, or -1 on failures
	 */
	 public function dbWrite (&$_resource, $_query);

	/**
	 * Retrieve the last auto generated ID value
	 * \param[in] $_resource Link with the database server
	 * \param[in] $_table Table to take the value from
	 * \param[in] $_field Name of the Auto field
	 * \return The last generated ID, or 0 when not found
	 */
	 public function dbInsertId (&$_resource, $_table, $_field);
	
	/**
	 * Get the number of rows in a dataset
	 * \param[in] $_data as returned by DbDriver::dbRead()
	 * \return Number of rows in the set
	 */
	 public function dbRowCount (&$_data);

	/**
	 * Get the next record from a dataset
	 * \param[in] $_data as returned by DbDriver::dbRead()
	 * \return Record as an associative array (fieldname => fieldvalue)
	 */
	 public function dbFetchNextRecord (&$_data);

	/**
	 * Clear a dataset
	 * \param[in] $_data as returned by DbDriver::dbRead()
	 */
	 public function dbClear (&$_data);

	/**
	 * Close a database connection
	 * \param[in] $_resource Link with the database server
	 * \return True on success, false on failures
	 */
	 public function dbClose (&$_resource);

	 /**
	 * Escape a given string for use in queries
	 * This method is implemented by class DbDefaults
	 * \param[in] $_string The input string
	 * \return String in SQL safe format
	 */
	public function dbEscapeString ($_string);

	/**
	 * Unescape a string fthat is formatted for use in SQL
	 * This method is implemented by class DbDefaults
	 * \param[in] $_string The input string in SQL safe format
	 * \return String without SQL formatting
	 */
	public function dbUnescapeString ($_string);

	/**
	 * Inplementation of the SQL COUNT() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments, which is required by syntax
	 * \return Complete SQL function code
	 */
	public function functionCount($_field, array $_arguments = array());

	/**
	 * Inplementation of the SQL IF() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments. This array should be
	 * in the format (check, value, then, else), e.g. array('<', 5 , 'Less then 5', '5 or more')
	 * \return Complete SQL function code
	 */
	public function functionIf($_field, array $_arguments = array());

	/**
	 * Inplementation of the SQL IFNULL() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments. The array should have 1 element, which is the
	 * default value when $_field is empty. Note for literal string values this field must be quoted, e.g. "'value'"!
	 * \return Complete SQL function code
	 */
	public function functionIfnull($_field, array $_arguments = array());

	/**
	 * Inplementation of the SQL CONCAT() function.
	 * \param[in] $_field Name of the field
	 * \param[in] $_arguments Array with arguments.  The array should have 1 element, which is the
	 * value that will be concatenaterd to $_field. Note for literal string values this field must be quoted, e.g. "'value'"!
	 * \return Complete SQL function code
	 */
	public function functionConcat($_field, array $_arguments = array());
}
