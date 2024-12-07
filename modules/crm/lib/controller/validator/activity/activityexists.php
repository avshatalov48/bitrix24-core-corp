<?php

namespace Bitrix\Crm\Controller\Validator\Activity;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Validator;
use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

class ActivityExists implements Validator
{
	private Broker|null $activityBroker;

	public function __construct(
		Broker|null $activityBroker = null,
	)
	{
		$this->activityBroker = $activityBroker ?? Container::getInstance()->getActivityBroker();
	}

	/**
	 * @param numeric $value
	 * @return Result
	 * @throws ArgumentTypeException
	 */
	public function validate(mixed $value): Result
	{
		if (!is_numeric($value))
		{
			throw new ArgumentTypeException('value', 'numeric');
		}

		$value = (int)$value;

		$activity = $this->activityBroker->getById($value);
		if ($activity === null)
		{
			return (new Result())->addError(ErrorCode::getNotFoundError());
		}

		return (new Result());
	}
}
