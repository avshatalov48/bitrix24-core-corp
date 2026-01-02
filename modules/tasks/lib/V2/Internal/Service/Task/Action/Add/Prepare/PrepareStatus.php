<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

class PrepareStatus implements PrepareFieldInterface
{
	use ConfigTrait;

	/**
	 * @throws TaskFieldValidateException
	 */
	public function __invoke(array $fields): array
	{
		if (!isset($fields['STATUS']))
		{
			return $fields;
		}

		$fields['STATUS'] = (int)$fields['STATUS'];

		if ($fields['STATUS'] === Status::NEW)
		{
			$fields['STATUS'] = Status::PENDING;
		}

		if ($fields['STATUS'] === Status::PENDING)
		{
			return $fields; // default status, so we don't set STATUS_CHANGED_DATE
		}

		$validValues = [
			Status::PENDING,
			Status::IN_PROGRESS,
			Status::SUPPOSEDLY_COMPLETED,
			Status::COMPLETED,
			Status::DEFERRED,
		];

		if (!in_array($fields['STATUS'], $validValues, true))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_INCORRECT_STATUS'));
		}

		$nowDateTimeString = UI::formatDateTime(User::getTime());

		if (!isset($fields['STATUS_CHANGED_DATE']))
		{
			$fields['STATUS_CHANGED_DATE'] = $nowDateTimeString;
		}

		return $fields;
	}
}