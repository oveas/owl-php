<?php
/**
 * \file
 * \ingroup OWL_LIBRARY
 * This file defines the message text for all status codes in UK English
 * \version $Id: owl.messages.php,v 1.1 2008-08-22 12:02:13 oscar Exp $
 */

$GLOBALS['messages'] = array (
	  OWL_STATUS_OK			=> 'Normal successfull completion'
	, OWL_STATUS_WARNING	=> 'Operation ended with a warning'
	, OWL_STATUS_ERROR		=> 'Operation ended with an error'
	, OWL_STATUS_BUG		=> 'A programming bug was found in $p1$ on line $p2$'
	, OWL_STATUS_FNF		=> 'File $p1$ not found'
	, OWL_STATUS_ROPENERR	=> 'Error openening $p1$ for read'
	, OWL_STATUS_WOPENERR	=> 'Error openening $p1$ for write'
	, OWL_STATUS_NOKEY		=> 'No security key could be found'
	, OWL_STATUS_IVKEY		=> 'Given security key does not match with this server'
	, LOGGING_OPENERR		=> 'Cannot open logfile <i>$p1$</i> for write'
	, SESSION_INVUSERNAME	=> 'Username does not exist'
	, SESSION_NODATASET		=> 'Session was created without a dataset'
	, SESSION_INVPASSWORD	=> 'Password does not match'
	, SESSION_TIMEOUT		=> 'Your session timed out - please log in again'
	, SESSION_NOACCESS		=> 'You have no access to this resource'
	, SESSION_DISABLED		=> 'SESSION_DISABLED'
	, SESSION_IVSESSION		=> 'SESSION_IVSESSION'
	, DBHANDLE_OPENED		=> 'Database $p1$ opened with ID $p2$'
	, DBHANDLE_QPREPARED	=> 'Prepared SQL statement for $p1$: <i>$p2$</i>'
	, DBHANDLE_ROWSREAD		=> '$p2$ rows returned to $p4$ (line $p3$) with query: <i>$P1$</i>'
	, DBHANDLE_ROWCOUNT		=> '$p2$ rows where successfully $p1$'
	, DBHANDLE_CONNECTERR	=> 'Error connecting to database server $p1$ with username $p2$ and password $p3$'
	, DBHANDLE_OPENERR		=> 'Error ($p2$) opening database $p1$: <i>$p3$</i>'
	, DBHANDLE_DBCLOSED		=> 'Attemt to read from a closed database'
	, DBHANDLE_QUERYERR		=> 'Invalid SQL Query in $p3$ at line $p2$: <i>$p1$</i>'
	, DBHANDLE_CREATERR		=> 'Error ($p2$) creating database $p1$: <i>$p3$</i>'
	, DBHANDLE_NODATA		=> 'Query had no results'
	, DBHANDLE_IVTABLE		=> 'Attemt to read from a non- existing database table'
	, DATA_KEYSET			=> 'Variable $p1$ locked as a key'
	, DATA_JOINSET			=> 'Table join ($p1$) has been defined on $p2$ and $p3$'
	, DATA_PREPARED			=> 'Prepared database query for $p1$'
	, DATA_NOTFOUND			=> 'No matching data found for $p1$'
	, DATA_NOSELECT			=> 'No selection criteria for the database query preparation'
	, DATA_NOSUCHFLD		=> 'Fieldname $p1$ does not exist in the current dataset'
	, DATA_IVARRAY			=> 'Array $p1$ is of an invalid type'
	, DATA_AMBFIELD			=> 'The variable $p1$ occured more than once'
	, DATA_NODBLINK			=> 'A database query shoukld be prepared, but there is no database handler set yet'
	, DATA_IVPREPARE		=> 'A database query was prepared with an invalid prepare flag'
	, DATA_IVRESET			=> 'The object was reset with an invalid reset flag'
	, FILE_NOSUCHFILE		=> ''
	, FILE_ENDOFFILE		=> ''
	, FILE_OPENOPENED		=> ''
	, FILE_CLOSECLOSED		=> ''
	, FILE_OPENERR			=> ''
);