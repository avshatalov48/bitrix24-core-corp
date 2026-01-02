<?php

class TasksException extends \Bitrix\Tasks\Exception
{
	const TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE  = 0x000001;
	const TE_ACCESS_DENIED                     = 0x100002;
	const TE_ACTION_NOT_ALLOWED                = 0x000004;
	const TE_ACTION_FAILED_TO_BE_PROCESSED     = 0x000008;
	const TE_TRYED_DELEGATE_TO_WRONG_PERSON    = 0x000010;
	const TE_FILE_NOT_ATTACHED_TO_TASK         = 0x000020;
	const TE_UNKNOWN_ERROR                     = 0x000040;
	const TE_FILTER_MANIFEST_MISMATCH          = 0x000080;
	const TE_WRONG_ARGUMENTS                   = 0x000100;
	const TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE  = 0x000200;
	const TE_SQL_ERROR                         = 0x000400;

	const TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE = 0x100000;

	private static $errSymbolsMap = array(
		'TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE'  => 0x000001,
		'TE_ACCESS_DENIED'                     => 0x100002,
		'TE_ACTION_NOT_ALLOWED'                => 0x000004,
		'TE_ACTION_FAILED_TO_BE_PROCESSED'     => 0x000008,
		'TE_TRYED_DELEGATE_TO_WRONG_PERSON'    => 0x000010,
		'TE_FILE_NOT_ATTACHED_TO_TASK'         => 0x000020,
		'TE_UNKNOWN_ERROR'                     => 0x000040,
		'TE_FILTER_MANIFEST_MISMATCH'          => 0x000080,
		'TE_WRONG_ARGUMENTS'                   => 0x000100,
		'TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE'  => 0x000200,
		'TE_SQL_ERROR'                         => 0x000400,

		'TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE' => 0x100000
	);

	public function checkIsActionNotAllowed()
	{
		return $this->checkOfType(self::TE_ACTION_NOT_ALLOWED);
	}

	public function checkOfType($type)
	{
		return $this->getCode() & $type;
	}

	protected function dumpAuxError()
	{
		return false;
	}

	public function __construct($message = false, $code = 0)
	{
		$parameters = array();

		if(!$message)
		{
			$message = $GLOBALS['APPLICATION']->GetException();
		}

		// exception extra data goes to log
		if($this->checkOfType(self::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE) && $message !== false)
		{
			$parameters['AUX']['ERROR'] = unserialize($message, ['allowed_classes' => false]);
		}

		parent::__construct(
			$message,
			$parameters,
			array(
				'CODE' => $code
			)
		);
	}

	/**
	 * @deprecated
	 */
	public static function renderErrorCode($e)
	{
		$errCode    = $e->getCode();
		$strErrCode = $errCode . '/';

		if ($e instanceof TasksException)
		{
			$strErrCode .= 'TE';

			foreach (self::$errSymbolsMap as $symbol => $code)
			{
				if ($code & $errCode)
					$strErrCode .= '/'.mb_substr($symbol, 3);
			}
		}
		elseif ($e instanceof CTaskAssertException)
			$strErrCode .= 'CTAE';
		else
			$strErrCode .= 'Unknown';

		return ($strErrCode);
	}

	public function isSerialized(): bool
	{
		try
		{
			$result = unserialize($this->getMessage(), ['allowed_classes' => false]);
			if ($result === false)
			{
				return false;
			}

			return true;
		}
		catch (ErrorException)
		{
			return false;
		}
	}
}