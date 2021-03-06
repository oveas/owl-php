<?php
/**
 * \file
 * This file defines the Oveas Web Library Dispatcher class
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

define ('TT_DISPATCHER_NAME', 'd'); //< Formfield/HTTP var name for the dispatcher

/**
 * \ingroup TT_BO_LAYER
 * Define the dispatcher. This class calls the proper method based in the request or form data
 * \brief Dispatcher singleton
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Nov 23, 2010 -- O van Eijk -- initial version
 */
class Dispatcher extends _TT
{
	/**
	 * integer - self reference
	 */
	private static $instance;

	/**
	 * string - A dispatcher registered for callback
	 */
	private $dispatcher;

	/**
	 * Constructor
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function __construct ()
	{
		parent::init(__FILE__, __LINE__);
		$this->dispatcher = null;
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
		if (!Dispatcher::$instance instanceof self) {
			Dispatcher::$instance = new self();
		}
		return Dispatcher::$instance;
	}

	/**
	 * Translate a given dispatcher to the URL encoded format
	 * \param[in] $_dispatcher Dispatcher as an indexed array with the following keys:
	 * 	- application: Name of the application as it appears in the TT application table
	 * 	- include_path: A path relative from the application's toplevel URL as it appears in the TT application table.
	 * Alternatively, a constant specifying a complete URL can be given
	 * 	- class_file: Filename, this can be the full file name ("class.myclass.php") or just the name ("myclass"). When omitted, it defaults to the classname (e.g. "MyClass") in lowercase.
	 * 	- class_name Name of the class.
	 * 	- method_name: Method that will be called when the form is submitted.
	 * 	- argument: An optional argument for the method called. The method which is called by the dispatcher must accept this argument type. If ommitted, no arguments will be passed by the dispatcher
	 * For short, a string in the format "application#include_path-path#class_file#class_name#method_name[#argument]"
	 * may also be given.
	 * \note The argument cannot have the integer value '0'
	 * \return URL encoded dispatcher
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function composeDispatcher($_dispatcher)
	{
		if (is_array($_dispatcher)) {
			foreach (array('application', 'include_path','class_name','method_name') as $_req) {
				if (!array_key_exists($_req, $_dispatcher)) {
					$this->setStatus (__FILE__, __LINE__, DISP_IVDISPATCH, $_req);
					return ($this->severity);
				}
			}
			if (array_key_exists('argument', $_dispatcher)) {
				if (is_array($_dispatcher['argument'])) {
					$_argument = serialize($_dispatcher['argument']);
				} else {
					$_argument = $_dispatcher['argument'];
				}
			} else {
				$_argument = 0;
			}
			$_dispatcher = $_dispatcher['application']
				.'#'.$_dispatcher['include_path']
				.'#'.(array_key_exists('class_file', $_dispatcher)?$_dispatcher['class_file']:strtolower($_dispatcher['class_name']))
				.'#'.$_dispatcher['class_name']
				.'#'.$_dispatcher['method_name']
				.'#'.$_argument;
		}
		return bin2hex(ttCrypt($_dispatcher));
	}

	/**
	 * Get the dispatcher info from the formdata, load the specified classfile and call the
	 * method specified.
	 * \param[in] $_dispatcher An optional dispatcher can be given (\see Dispatcher::composeDispatcher()
	 * for the format). When omitted, the dispatcher will be taken from the formdata.
	 * \return On errors during dispatch, the severity level, otherwise the return value
	 * of the given method. If no dispatcher code was found, DISP_NOARG is returned
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function dispatch($_dispatcher = null)
	{
		$_form = null;
		if ($_dispatcher === null) {
			$_form = TT::factory('FormHandler');

			$_dispatcher = $_form->get(TT_DISPATCHER_NAME);
			if ($_form->getStatus() === FORM_NOVALUE || !$_dispatcher) {
				$this->setStatus(__FILE__, __LINE__, DISP_NOARG);
				return DISP_NOARG;
			}
			$_destination = $this->decodeDispatcher($_dispatcher);
		} else {
			$_destination = $this->decodeDispatcher($_dispatcher);
		}

		$_logger = TT::factory('LogHandler', 'so');
		$_logger->logSession($_destination, $_form);

		if (defined($_destination['include_path'])) {
			$_inc_path = constant($_destination['include_path']);
		} else {
			$_inc_path = TT_APPS_ROOT . '/'.$this->getExternalApplication($_destination['application']).'/'.$_destination['include_path'];
		}

		if (!TTloader::getClass($_destination['class_file'], $_inc_path)) {
			$this->setStatus (__FILE__, __LINE__, DISP_NOCLASSF, array($_destination['class_file'], "$_inc_path/".$_destination['class_file']));
			return ($this->severity);
		}

		if (!class_exists($_destination['class_name'])) {
			$this->setStatus (__FILE__, __LINE__, DISP_NOCLASS, $_destination['class_name']);
			return ($this->severity);
		}

		if (method_exists($_destination['class_name'], 'getReference')) {
			// user call_user_func() to be compatible with PHP v < 5.3.0
			$_handler = call_user_func (array($_destination['class_name'], 'getReference'));
		} else {
			$_handler = new $_destination['class_name']();
		}

		if (!method_exists($_handler, $_destination['method_name'])) {
			$this->setStatus (__FILE__, __LINE__, DISP_NOMETHOD, array($_destination['method_name'], $_destination['class_name']));
			return ($this->severity);
		}
		if ($_destination['argument'] !== 0) {
			return $_handler->{$_destination['method_name']}($_destination['argument']);
		} else {
			return $_handler->{$_destination['method_name']}();
		}
	}

	/**
	 * Check the format a a dispatcher and decode it
	 * \param[in] $_dispatcher Dispatcher
	 * \return Dispatcher as an indexed array
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function decodeDispatcher($_dispatcher)
	{
		if (is_array($_dispatcher)) {
			return ($_dispatcher);
		}
		$_dElements = explode('#', $_dispatcher);
		if (!(count($_dElements) >= 5)) {
			$_dispatcher = ttCrypt(pack ("H*", $_dispatcher));
			$_dElements = explode('#', $_dispatcher);
		}
		$_d['application'] = array_shift($_dElements);
		$_d['include_path'] = array_shift($_dElements);
		$_d['class_file'] = array_shift($_dElements);
		$_d['class_name'] = array_shift($_dElements);
		$_d['method_name'] = array_shift($_dElements);
		$_arg = ((count($_dElements) > 0) ? $_dElements[0] : 0);
		if (!$_arg) {
			$_d['argument'] = 0;
		} else {
			if (isSerialized($_arg, $_d['argument']) === false) {
				$_d['argument'] = $_arg;
			}
		}
		return ($_d);
	}

	/**
	 * Register a callback that wal later be retrieved as dispatcher
	 * \param[in] $_dispatcher Dispatched, \see Dispatcher::composeDispatcher() for the format
	 * \return True on success, false on failure
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function registerCallback($_dispatcher)
	{
		if ($this->dispatcher !== null) {
			$this->setStatus (__FILE__, __LINE__, DISP_ALREGIST);
			return (false);
		}
		$this->dispatcher = $this->composeDispatcher($_dispatcher);
		if (!$this->succeeded()) {
			$this->dispatcher = null;
			return (false);
		}
		return (true);
	}

	/**
	 * Add an argument to a previously registered callback dispatcher
	 * \param[in] $_argument Argument, must be an array type. When non- arrays should be passed as arguments, the must be set when the callback is registered already
	 * \return True on success, false on failure
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function registerArgument(array $_argument)
	{
		if ($this->dispatcher === null) {
			$this->setStatus (__FILE__, __LINE__, DISP_NOTREGIST);
			return (false);
		}
		$_dispatcher = $this->decodeDispatcher($this->dispatcher);

		if ($_dispatcher['argument'] === 0) {
			$_dispatcher['argument'] = $_argument;
		} else {
			if (!is_array($_dispatcher['argument'])) {
				$this->setStatus (__FILE__, __LINE__, DISP_INCOMPAT);
				return (false);
			}
			$_dispatcher['argument'] = $_argument + $_dispatcher['argument'];
		}
		$this->dispatcher = $this->composeDispatcher($_dispatcher);
		return ($this->succeeded());
	}

	/**
	 * Retrieve a previously set (callback) dispatcher. The (callback) dispatcher is cleared immediatly.
	 * \return The dispatcher, or null when no dispatched was registered
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function getCallback()
	{
		$_dispatcher = $this->dispatcher;
		$this->dispatcher = null; // reset
		return ($_dispatcher);
	}

	/**
	 * Initialise an external application for which a contentarea is dispatched
	 * \param[in] $_applicCode Code of the application
	 * \return URL of the application, relative from TT_SITE_TOP, based on the application name
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	private function getExternalApplication($_applicCode)
	{
		if (($_top = TTCache::getApplic($_applicCode, TT_APPITM_TOP)) !== null) {
			return $_top;
		}
		$_dataset = new DataHandler('applications');
		if (ConfigHandler::get ('database', 'tttables', true)) {
			$_dataset->setPrefix(ConfigHandler::get ('database', 'ttprefix'));
		}
		$_dataset->set('code', $_applicCode);
		$_dataset->setKey ('code');
		$_dataset->set('url', null, null, null, array('match' => array(DBMATCH_NONE)));
		$_dataset->prepare ();
		$_dataset->db($_data, __LINE__, __FILE__);
		$_dbstat = $_dataset->dbStatus();
		if ($_dbstat === DBHANDLE_NODATA) {
			$this->setStatus (__FILE__, __LINE__, DISP_NOSUCHAPPL, array($_applicCode));
			return null;
		}
		// New application, so load it now
		TTloader::loadApplication($_applicCode, false);
		return $_data[0]['url'];
	}
}

/*
 * Register this class and all status codes
 */
Register::registerClass ('Dispatcher', TT_APPNAME);

//Register::setSeverity (TT_DEBUG);

Register::setSeverity (TT_INFO);
Register::registerCode ('DISP_NOARG');

//Register::setSeverity (TT_OK);
Register::setSeverity (TT_SUCCESS);

//Register::setSeverity (TT_WARNING);
Register::registerCode ('DISP_INSARG');
Register::registerCode ('DISP_NOTREGIST');

Register::setSeverity (TT_BUG);
Register::registerCode ('DISP_ALREGIST');
Register::registerCode ('DISP_NOSUCHAPPL');

Register::setSeverity (TT_ERROR);
Register::registerCode ('DISP_IVDISPATCH');
Register::registerCode ('DISP_INCOMPAT');
Register::registerCode ('DISP_NOCLASS');
Register::registerCode ('DISP_NOCLASSF');
Register::registerCode ('DISP_NOMETHOD');

//Register::setSeverity (TT_FATAL);
//Register::setSeverity (TT_CRITICAL);
