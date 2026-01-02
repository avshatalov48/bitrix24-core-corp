<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\User;

class PrepareStatus implements PrepareFieldInterface
{
	use ConfigTrait;
	
	public function __invoke(array $fields, array $fullTaskData): array
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

		$nowDateTimeString = \Bitrix\Tasks\UI::formatDateTime(User::getTime());

		if (!isset($fields['STATUS_CHANGED_DATE']))
		{
			$fields['STATUS_CHANGED_DATE'] = $nowDateTimeString;
		}

		if ((int)$fullTaskData['STATUS'] === $fields['STATUS'])
		{
			return $fields;
		}

		if (!isset($fields['STATUS_CHANGED_BY']))
		{
			$fields['STATUS_CHANGED_BY'] = $this->config->getUserId();
		}

		if (
			$fields['STATUS'] === Status::COMPLETED
			|| $fields['STATUS'] === Status::SUPPOSEDLY_COMPLETED
		)
		{
			$fields['CLOSED_BY'] = $this->config->getUserId();
			$fields['CLOSED_DATE'] = $nowDateTimeString;
		}
		else
		{
			$fields['CLOSED_BY'] = false;
			$fields['CLOSED_DATE'] = false;

			if (
				$fields['STATUS'] === Status::IN_PROGRESS
				&& !isset($fields['DATE_START'])
			)
			{
				$fields['DATE_START'] = $nowDateTimeString;
			}
		}

		return $fields;
	}
}