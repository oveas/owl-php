<?php
/**
 * \file
 * \ingroup TT_SO_LAYER
 * This file loads the class to handle timers
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

// Main timer, will be started by TTloader when timers are enabled
define ('TT_MAIN_TIMER', 'TTmain');

/**
 * \ingroup TT_SO_LAYER
 * This abstract class sets up timers that can be used to check the runtime of both the total
 * and parts.
 *
 * To enable timers, the constant TT_TIMERS_ENABLES must be defined by the application <em>before</em>
 * TTloader.php is included, e.g.:
 * \code
 * define ('TT_TIMERS_ENABLED', true);
 * require (TT_ROOT . '/TTloader.php');
 * \endcode
 *
 * Timers can be started anywhere in the code. When timers are not enabled, it won't have any effect.
 * Starting timers can be done with TTTimers::startTimer('name'). Later in the code they can be stopped
 * with TTTimers::stopTimer('name'), where 'name' must be unique.
 *
 * At the end of the run, the total running time of the application will be displayed, end the running
 * time of the individual timers (the part between startTimer() and stopTimer()) with their percentage
 * of the total time.
 *
 * Timers that are not stopped, will be timed until the application rundown.
 * \brief Timer handler
 * \author Oscar van Eijk, Oveas Functionality Provider
 * \version May 21, 2011 -- O van Eijk -- initial version
 */
abstract class TTTimers
{
	/**
	 * Array holding the timers that are currently active
	 */
	static private $activeTimers = array();

	/**
	 * Array holding all timers and their values
	 */
	static private $timers = array();

	/**
	 * Array with warning messages if applicable
	 */
	static private $warnings = array();

	/**
	 * Get the current microtime
	 * \return Microtime as a floating int value
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static private function mtimeFloat()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * Start a new timer, creating an array key with the actual timestamp.
	 * \param[in] $name Unique name of the timer.
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function startTimer($name)
	{
		if (!TT_TIMERS_ENABLED) {
			return;
		}
		if (array_key_exists($name, self::$timers)) {
			self::$warnings[] = ContentArea::translate('Timer active', $name);
			return;
		}
		self::$timers[$name] = self::mtimeFloat();
		self::$activeTimers[$name] = 1;
	}

	/**
	 * Stop an active timer and update the array key with the execution time since the start timestamp.
	 * \param[in] $name Unique name of the timer.
	 * \return
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function stopTimer($name)
	{
		if (!TT_TIMERS_ENABLED) {
			return;
		}
		if (!array_key_exists($name, self::$activeTimers)) {
			self::$warnings[] = ContentArea::translate('Timer not active', $name);
			return;
		}
		unset (self::$activeTimers[$name]);
		self::$timers[$name] = (self::mtimeFloat() - self::$timers[$name]);
	}

	/**
	 * Show all timers. This method is called by OCLrundown.php at the end of the run
	 * \author Oscar van Eijk, Oveas Functionality Provider
	 */
	static public function showTimer ()
	{
		if (!TT_TIMERS_ENABLED) {
			return;
		}
		foreach (self::$activeTimers as $timer => $dummy) {
			self::stopTimer($timer);
		}
		$txt = '<hr/><em><u>' . ContentArea::translate('TT timers') . '</u></em><p>';

		$mem = memory_get_peak_usage(true)/1024;
		$time = self::$timers[TT_MAIN_TIMER];
		foreach (self::$timers as $timer => $value) {
			if ($timer !== TT_MAIN_TIMER) {
				$perc = ($value / $time) * 100;
				$txt .= ContentArea::translate('Timer value'
					, array($timer, $value, $perc)) . '<br/>';
			}
		}
		$txt .= ContentArea::translate('Timer total', array($time, $mem));
		$txt .= '</p>';
		OutputHandler::outputPar($txt, 'TTtimers');

		if (count(self::$warnings) > 0) {

			$txt = '<hr/><em><u>' . ContentArea::translate('Timer warnings') . '</u></em><p>';
			foreach (self::$warnings as $warn) {
				$txt .= $warn . "<br/>";
			}
			$txt .= '</p>';
			// TODO Make sure timers are added to the Footer (but right now the fotter is already sent to the browser - we need decent JavScript code first)
//			TTCache::get(TTCACHE_OBJECTS, CONTAINER_FOOTER)->addToContent($txt);
			OutputHandler::outputPar($txt, 'TTtimerWarnings');
		}

	}
}
