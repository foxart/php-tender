<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 12/24/2016
 * Time: 5:37 PM
 */

namespace fa\core\services;

use fa\core\classes\Singleton;
use fa\core\faf;

class Debug extends Singleton {

	public static $instance;

	/**
	 * Dumps information about multiple variables
	 *
	 * @return void
	 */
	public static function dumpMulti() {
		// get variables to dump
		$args = func_get_args();
		// loop through all items to output
		foreach ($args as $arg) {
			self::dump($arg);
		}
	}

	/**
	 * @param mixed $variable Variable to dump
	 * @param bool $output
	 *
	 * @return string
	 * @return void
	 */
	public function dump($variable, $output = TRUE) {
		// prepare the output string
//		$html = '';
		// start the output buffering
		ob_start();
		// generate the output
		echo 'file: <span style="font-weight: bold;">' . faf::backtrace()->get(1)['file'] . '</span><br/>';
		echo 'line: <span style="font-weight: bold;">' . faf::backtrace()->get(1)['line'] . '</span><br/>';
		echo '<div style="display: block; margin: 5px 0 5px 0; padding: 0; height: 0px; border-top: solid 1px #000000;"></div>';
//		echo 'arguments: <span style="font-style: italic;"><br/>';
//		print_r(faf::backtrace()->get(1)['args']);
//		echo '</span><br/>';
//		dump(faf::backtrace()->get(1));
		var_dump($variable);
		// get the output
		$buffer = ob_get_clean();
		$maps = array(
			'string' => '/(string\((?P<length>\d+)\)) (?P<value>\"(?<!\\\).*\")/i',
			'array' => '/\[\"(?P<key>.+)\"(?:\:\"(?P<class>[a-z0-9_\\\]+)\")?(?:\:(?P<scope>public|protected|private))?\]=>/Ui',
			'countable' => '/(?P<type>array|int|string)\((?P<count>\d+)\)/',
			'resource' => '/resource\((?P<count>\d+)\) of type \((?P<class>[a-z0-9_\\\]+)\)/',
			'bool' => '/bool\((?P<value>true|false)\)/',
			'float' => '/float\((?P<value>[0-9\.]+)\)/',
			'object' => '/object\((?P<class>[a-z_\\\]+)\)\#(?P<id>\d+) \((?P<count>\d+)\)/i',
		);
		foreach ($maps as $function => $pattern) {
			$buffer = preg_replace_callback($pattern, array(
				'self',
				'_process' . ucfirst($function),
			), $buffer);
		}
		if ($output === FALSE) {
			return '<pre style="' . self::_getContainerCss() . '">' . $buffer . '</pre>';
		} else {
			echo '<pre style="' . self::_getContainerCss() . '">' . $buffer . '</pre>';

			return NULL;
		}
	}

	/**
	 * Process strings
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processString(array $matches) {
		$matches['value'] = htmlspecialchars($matches['value']);

		return '<span style="color: #0000FF;">string</span>(<span style="color: #1287DB;">' . $matches['length'] . ')</span> <span style="color: #6B6E6E;">' . $matches['value'] . '</span>';
	}

	/**
	 * Process arrays
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processArray(array $matches) {
		// prepare the key name
		$key = '<span style="color: #008000;">"' . $matches['key'] . '"</span>';
		$class = '';
		$scope = '';
		// prepare the parent class name
		if (isset($matches['class']) && !empty($matches['class'])) {
			$class = ':<span style="color: #4D5D94;">"' . $matches['class'] . '"</span>';
		}
		// prepare the scope indicator
		if (isset($matches['scope']) && !empty($matches['scope'])) {
			$scope = ':<span style="color: #666666;">' . $matches['scope'] . '</span>';
		}

		// return the final string
		return '[' . $key . $class . $scope . ']=>';
	}

	/**
	 * Process countables
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processCountable(array $matches) {
		$type = '<span style="color: #0000FF;">' . $matches['type'] . '</span>';
		$count = '(<span style="color: #1287DB;">' . $matches['count'] . '</span>)';

		return $type . $count;
	}

	/**
	 * Process boolean values
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processBool(array $matches) {
		return '<span style="color: #0000FF;">bool</span>(<span style="color: #0000FF;">' . $matches['value'] . '</span>)';
	}

	/**
	 * Process floats
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processFloat(array $matches) {
		return '<span style="color: #0000FF;">float</span>(<span style="color: #1287DB;">' . $matches['value'] . '</span>)';
	}

	/**
	 * Process resources
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processResource(array $matches) {
		return '<span style="color: #0000FF;">resource</span>(<span style="color: #1287DB;">' . $matches['count'] . '</span>) of type (<span style="color: #4D5D94;">' . $matches['class'] . '</span>)';
	}

	/**
	 * Process objects
	 *
	 * @param array $matches Matches from preg_*
	 *
	 * @return string
	 */
	private static function _processObject(array $matches) {
		return '<span style="color: #0000FF;">object</span>(<span style="color: #4D5D94;">' . $matches['class'] . '</span>)#' . $matches['id'] . ' (<span style="color: #1287DB;">' . $matches['count'] . '</span>)';
	}

	/**
	 * Get the CSS string for the output container
	 *
	 * @return string
	 */
	private static function _getContainerCss() {
		return self::_arrayToCss(array(
			'background-color' => '#d6ffef',
			'border' => '1px solid #bbb',
			'border-radius' => '4px',
			'-moz-border-radius' => '4px',
			'-webkit-border-radius' => '4px',
			'font-size' => '12px',
			'line-height' => '1.4em',
			'margin' => '30px',
			'padding' => '7px',
		));
	}

	/**
	 * Get the CSS string for the output header
	 *
	 * @return string
	 */
	private static function _getHeaderCss() {
		return self::_arrayToCss(array(
			'border-bottom' => '1px solid #bbb',
			'font-size' => '18px',
			'font-weight' => 'bold',
			'margin' => '0 0 10px 0',
			'padding' => '3px 0 10px 0',
		));
	}

	/**
	 * Convert a key/value pair array into a CSS string
	 *
	 * @param array $rules List of rules to process
	 *
	 * @return string
	 */
	private static function _arrayToCss(array $rules) {
		$strings = array();
		foreach ($rules as $key => $value) {
			$strings[] = $key . ': ' . $value;
		}

		return join('; ', $strings);
	}
}
