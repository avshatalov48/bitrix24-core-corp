<?php
namespace Bitrix\Timeman\Service;

use Bitrix\Main\Result;
use Bitrix\Timeman\Service\Exception\BaseServiceException;

class BaseService
{
	private $stopOnFirstError = true;

	/**
	 * @param $callbackFunction
	 * @return BaseServiceResult
	 */
	protected function wrapAction($callbackFunction)
	{
		try
		{
			return $callbackFunction();
		}
		catch (BaseServiceException $exception)
		{
			return $this->wrapResultOnException($exception->getResult());
		}
	}

	protected function wrapResultOnException($result)
	{
		return BaseServiceResult::createByResult($result);
	}

	/**
	 * @param Result|null $result
	 * @return mixed
	 * @throws BaseServiceException
	 */
	protected function safeRun($result = null)
	{
		if ($this->isSuccess($result))
		{
			return $result;
		}
		if ($this->stopOnFirstError)
		{
			throw (new BaseServiceException())->setResult($result);
		}
		// for now it always stops on first error
	}

	protected function isSuccess(Result $result)
	{
		$errorMessages = $result->getErrorMessages();
		return $result->isSuccess() || (!$result->isSuccess() && reset($errorMessages) === 'There is no data to update.');
	}
}