<?php
namespace Bitrix\Timeman\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class BaseServiceResult extends \Bitrix\Main\Result
{
	/**
	 * @param Result $result
	 * @return static
	 */
	public static function createByResult($result)
	{
		if ($result->isSuccess())
		{
			return new static();
		}
		return (new static)->addErrors($result->getErrors());
	}

	/**
	 * @param $text
	 * @return static
	 */
	public static function createWithErrorText($text, $code = 0)
	{
		return (new static())->addError(new Error($text, $code));
	}

	public static function isSuccessResult(\Bitrix\Main\Result $result)
	{
		return $result->isSuccess()
			   || (!$result->isSuccess() && reset($result->getErrorMessages()) === 'There is no data to update.');
	}

	/**
	 * @return Error|null
	 */
	public function getFirstError()
	{
		return empty($this->getErrors()) ? null : reset($this->getErrors());
	}
}