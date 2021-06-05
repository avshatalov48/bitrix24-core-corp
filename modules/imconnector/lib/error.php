<?php
namespace Bitrix\ImConnector;

/**
 * Error handling class.
 * @package Bitrix\ImConnector
 */
class Error extends \Bitrix\Main\Error
{
	/** @var string */
	protected $method;

	/** @var array */
	protected $params;

	/**
	 * Creates a new Error.
	 * @param string $message Message of the error.
	 * @param int|string $code Code of the error.
	 * @param string $method
	 * @param string|array $params
	 */
	public function __construct($message = '', $code = 0, $method = '', $params = Array())
	{
		parent::__construct($message, $code);

		$this->method = $method;

		$this->params = $params;

		$debugBacktrace = debug_backtrace();
		Log::write(array(
			'message' => $message,
			'code' => $code,
			'params' => $params,
			'file' => $debugBacktrace[0]['file'],
			'method' => $method,
			'line' => $debugBacktrace[0]['line'],
		), 'error');
	}

	/**
	 * Returns a method in which there was an error.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * Returns parameters which were transferred to a method at the time of emergence of an error.
	 *
	 * @return array|null
	 */
	public function getParams()
	{
		return $this->params;
	}
}