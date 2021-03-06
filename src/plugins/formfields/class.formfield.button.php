<?php
/**
 * \file
 * This file defines a button formfield element
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
 * \ingroup TT_PLUGINS
 * Formfield button elements
 * \brief Formfield
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version Oct 19, 2010 -- O van Eijk -- initial version
 */

class FormFieldButtonPlugin extends FormFieldPlugin
{
	/**
	 * Alternate text (image-type only)
	 */
	public $alt;

	/**
	 * Image src (image-type only)
	 */
	public $src;

	/**
	 * Class constructor;
	 * \param[in] $type Element type: button (default), image, submit or reset
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function __construct ($type = 'button')
	{
		parent::__construct();
		$this->type = strtolower($type);
	}

	/**
	 * Set the alt text for the button
	 * \param[in] $_value text
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function setText($_value)
	{
		$this->alt = $_value;
	}

	/**
	 * Set the image source for the button
	 * \param[in] $_value Image source
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function setSource($_value)
	{
		$this->src = $_value;
	}

	/**
	 * Return the HTML code to display the form element
	 * \return Textstring with the complete code for the form element
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	public function showElement ()
	{
		$_htmlCode = "<input type='$this->type'" . $this->getGenericFieldAttributes();
		if ($this->type == 'image') {
			if (!empty($this->alt)) {
				$_htmlCode .= " alt='$this->alt'";
			}
			if (!empty($this->src)) {
				$_htmlCode .= " src='$this->src'";
			}
		}
		$_htmlCode .= '/>';
		return $_htmlCode;
	}
}


//Register::setSeverity (TT_DEBUG);

//Register::setSeverity (TT_INFO);
//Register::setSeverity (TT_OK);
//Register::setSeverity (TT_SUCCESS);
//Register::registerCode ('FORM_RETVALUE');

//Register::setSeverity (TT_WARNING);

//Register::setSeverity (TT_BUG);

//Register::setSeverity (TT_ERROR);
//Register::setSeverity (TT_FATAL);
//Register::setSeverity (TT_CRITICAL);
