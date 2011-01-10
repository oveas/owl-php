<?php
/**
 * \file
 * This file defines the HTML Form class
 * \version $Id: class.form.php,v 1.3 2011-01-10 18:46:00 oscar Exp $
 */

OWLloader::getClass('formfield', OWL_UI_INC);

/**
 * \ingroup OWL_BO_LAYER
 * Define an HTML Form.
 * \brief Form Element class 
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Aug 29, 2008 -- O van Eijk -- initial version
 */
class Form extends BaseElement
{

	/**
	 * Array holding all field objects
	 * \private
	 */
	private $fields;

	/**
	 * String holding dispatch info.
	 * \private
	 */
	private $dispatcher;

	/**
	 * Method; post (default) or get
	 * \private
	 */
	private $method;

	/**
	 * Form encoding; application/x-www-form-urlencoded (defauls) or multipart/form-data
	 * \private
	 */
	private $enctype;

	/**
	 * Class constructor
	 * \param[in] $dispatcher Dispatcher as an indexed array with the followinf keys:
	 * 	- application: Name of the application. When the include path is no constant, it must be equal to
	 * the name of the directory directly under the server's document root.
	 * 	- include_path: A path relative from the application toplevel, or a constant
	 * 	- class_file: Filename, this can be the full file name ("class.myclass.php") or just the name ("myclass")
	 * 	- class_name Name of the class. This can be ommitted if it is equal to the classfile starting
	 * with a capital ("Myclass")
	 * 	- method_name: Methpd that will be called when the form is submitted. This method should accept
	 * no parameters, but must get the formdata using OWL::factory('FormHandler');
	 * For short, a string in the format "application#include_path-path#class_file#class_name#method_name"
	 * may also be given.
	 * \public
	 */
	public function __construct ($dispatcher)
	{
		_OWL::init();
		$this->fields = array();
		$this->method = 'POST';
		$this->enctype = 'application/x-www-form-urlencoded';
		
		if (is_array($dispatcher)) {
			foreach (array('application', 'include_path','class_file','method_name') as $_req)
			if (!array_key_exists($_req, $dispatcher)) {
				$this->set_status (FORM_IVDISPATCH, $_req);
				return ($this->severity);
			}
			$_dispatcher = $dispatcher['application']
				.'#'.$dispatcher['include_path']
				.'#'.$dispatcher['class_file']
				.'#'.(array_key_exists('class_name', $dispatcher)?$dispatcher['class_name']:'')
				.'#'.$dispatcher['method_name'];
		} else {
			$_dispatcher = $dispatcher;
		}
		$this->dispatcher = urlencode(owlCrypt($_dispatcher));
	}

	/**
	 * Set the form method
	 * \param[in] $method The method, GET and POST are supported
	 * \return Severity level of the object status
	 */
	public function setMethod ($method)
	{
		if ($method != 'GET' && $method != 'POST') {
			$this->set_status (FORM_IVMETHOD, $method);
			return ($this->severity);
		}
		$this->method = $method;
		return ($this->severity);
	}

	/**
	 * Set the form encoding
	 * \param[in] $enctype The encoding type, multipart/form-data and application/x-www-form-urlencoded
	 * are supported
	 * \return Severity level of the object status
	 */
	public function setEncoding ($enctype)
	{
		$enctype = strtolower($enctype);
		if ($enctype != 'multipart/form-data' && $enctype != 'application/x-www-form-urlencoded') {
			$this->set_status (FORM_IVENCODING, $enctype);
			return ($this->severity);
		}
		$this->enctype = $enctype;
		return ($this->severity);
	}
	
	/**
	 * Add a formfield to the formelement
	 * \param[in] $type Field type
	 * \param[in] $name Field name
	 * \param[in] $value Optional field value. For a selectlist type this must be an array, see FormFieldSelect::setValue()
	 * \param[in] $attributes Indexed array with additional values in the format, where the key must be a supported attributed
	 * for the given type.
	 * \return Severity level of the object status
	 */
	public function addField($type, $name, $value = '', $attributes = array())
	{
		if (in_array($name, $this->fields)) {
			// TODO This must be an addValue method; trigger an error here
//			// Object already exists, so it must be a multivalue (radio or select).
//			if (method_exists($this->fields[$name], 'addValue')) {
//				if (in_array(''))
//				$this->fields[$name]->addValue($value);
//			} else {
//				$this->set_status (FORM_NOMULTIVAL, array($name, $this->fields[$name]->getType()));
//				return $this->severity;
//			}
		} else {
			// Add a new object to the fieldlist
			$_subtype = '';

			if ($type == 'text' || $type == 'hidden' || $type == 'password') {
				$_subtype = $type;
				$type = 'text';
			}
			if ($type == 'button' || $type == 'image' || $type == 'submit' || $type == 'reset') {
				$_subtype = $type;
				$type = 'button';
			}

			if (!OWLloader::getClass('formfield.'.$type, OWL_UI_INC . '/formfields')) {
				$this->set_status (FORM_NOCLASS, $type);
				return ($this->severity);
			}
			$_className = 'FormField' . ucfirst($type);

			$this->fields[$name] = new $_className($_subtype);

			$this->fields[$name]->setName($name);
			$this->fields[$name]->setValue($value);
		}

		if (count($attributes) > 0) {
			$this->setFieldAttributes($name, $attributes);
		}
		return ($this->severity);
	}

	/**
	 * Set one or more formfield attributes
	 * \param[in] $index Index of the fieldobject
	 * \param[in] $attributes array with object name and values in the format ('attrib' => 'value')
	 */
	public function setFieldAttributes($index, $attributes)
	{
		foreach ($attributes as $_k => $_v) {
			$_method = 'set' . ucfirst($_k);
			if (method_exists($this->fields[$index], $_method)) {
				$this->fields[$index]->$_method($_v);
			} else {
				$this->set_status (FORM_NOATTRIB, array($_k, $this->fields[$index]->getType()));
			}
		}
	}

	/**
	 * Set one or more formfield events
	 * \param[in] $index Index of the fieldobject
	 * \param[in] $events array with eventnames and JavaScript code in the format ('event' => 'action')
	 */
	public function setFieldEvents($index, $events)
	{
		foreach ($events as $_e => $_a) {
			$this->fields[$index]->setEvent($_e, $_a);
		}
	}

	/**
	 * Get the HTML code for a given field
	 * \param[in] $name Fieldname
	 * \return HTML code defining the field
	 */
	public function showField($name)
	{
		if (in_array($name, $this->fields)) {
			$this->set_status (FORM_NOSUCHFIELD, array($name));
			return null;
		}
		return $this->fields[$name]->getFieldCode();
	}

	/**
	 * Return the form code to open the form
	 * \return HTML code
	 */
	public function openForm()
	{
		return '<form action="'.$_SERVER['PHP_SELF'].'" '
			. parent::getAttributes()
			. ' enctype="'.$this->enctype.'"'
			. ' method="'.$this->method.'">'."\n";
	}

	/**
	 * Close the form and set a hidden field defining the dispatcher
	 * \return HTML code
	 */
	public function closeForm()
	{
		$this->addField('hidden', 'owl_dispatch', $this->dispatcher);
		return $this->showField('owl_dispatch') . '</form>'."\n";
	}
}

/*
 * Register this class and all status codes
 */
Register::register_class ('Form');

//Register::set_severity (OWL_DEBUG);

//Register::set_severity (OWL_INFO);
//Register::set_severity (OWL_OK);
Register::set_severity (OWL_SUCCESS);
//Register::register_code ('FORM_RETVALUE');

Register::set_severity (OWL_WARNING);
Register::register_code ('FORM_NOMULTIVAL');
Register::register_code ('FORM_IVMETHOD');
Register::register_code ('FORM_IVENCODING');

//Register::set_severity (OWL_BUG);

Register::set_severity (OWL_ERROR);
Register::register_code ('FORM_IVDISPATCH');
Register::register_code ('FORM_NOCLASS');
Register::register_code ('FORM_NOATTRIB');
Register::register_code ('FORM_NOSUCHFIELD');

//Register::set_severity (OWL_FATAL);
//Register::set_severity (OWL_CRITICAL);
