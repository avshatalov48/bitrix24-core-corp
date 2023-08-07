<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Localization\Loc;

/**
 * Error handling class.
 * @package Bitrix\ImOpenLines
 */
class Error extends \Bitrix\Main\Error
{
	public const
		WRONG_TYPE = 'WRONG_MESSAGE_TYPE',
		WRONG_PARAMETER = 'WRONG_PARAMETER',
		WRONG_SENDER = 'WRONG_SENDER',
		WRONG_RECIPIENT = 'WRONG_RECIPIENT',
		WRONG_TARGET_CHAT = 'WRONG_TARGET_CHAT',
		WRONG_COLOR = 'WRONG_COLOR',
		WRONG_PARENT_CHAT = 'WRONG_PARENT_CHAT',
		WRONG_PARENT_MESSAGE = 'WRONG_PARENT_MESSAGE',
		WRONG_DISAPPEARING_DURATION = 'WRONG_DISAPPEARING_DURATION',
		ALREADY_DISAPPEARING = 'ALREADY_DISAPPEARING',
		ACCESS_DENIED = 'ACCESS_DENIED',
		NOT_FOUND = 'NOT_FOUND',
		BEFORE_SEND_EVENT = 'EVENT_MESSAGE_SEND',
		FROM_OTHER_MODULE = 'FROM_OTHER_MODULE',
		CREATION_ERROR = 'CREATION_ERROR',
		ID_EMPTY_ERROR = 'ID_EMPTY_ERROR'
	;

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
	public function __construct($message = '', $code = 0, $method = '', $params = [])
	{
		parent::__construct($message, $code);

		$this->method = $method;

		$this->params = $params;

		$debugBacktrace = debug_backtrace();
		Log::write([
			'message' => $message,
			'code' => $code,
			'params' => $params,
			'file' => $debugBacktrace[0]['file'],
			'method' => $method,
			'line' => $debugBacktrace[0]['line'],
		]);
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

	protected function loadErrorMessage($code, $replacements): string
	{
		return Loc::getMessage("ERROR_OL_{$code}", $replacements) ?: '';
	}

	protected function loadErrorDescription($code, $replacements): string
	{
		return Loc::getMessage("ERROR_OL_{$code}_DESC", $replacements) ?: '';
	}
}