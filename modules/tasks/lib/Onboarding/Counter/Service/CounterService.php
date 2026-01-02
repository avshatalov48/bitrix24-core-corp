<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Counter\Service;

use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Validation\ValidationService;
use Bitrix\Tasks\Onboarding\Counter\CounterServiceInterface;
use Bitrix\Tasks\Onboarding\Internal\Model\JobCountTable;
use Bitrix\Tasks\Onboarding\Transfer\JobCode;
use Bitrix\Tasks\Onboarding\Transfer\JobCodes;
use Throwable;

class CounterService implements CounterServiceInterface
{
	protected ValidationService $validationService;

	public function __construct()
	{
		$this->init();
	}

	public function increment(JobCode $code): Result
	{
		$validationResult = $this->validationService->validate($code);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$result = new Result();

		$connection = Application::getConnection();

		$table = JobCountTable::getTableName();

		$sql = "UPDATE {$table} SET JOB_COUNT = JOB_COUNT + 1 WHERE CODE = '{$code->code}'";

		try
		{
			$connection->query($sql);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		JobCountTable::cleanCache();

		return $result;
	}

	protected function init(): void
	{
		$this->validationService = ServiceLocator::getInstance()->get('main.validation.service');
	}
}