<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\SocialNetwork\GroupProvider;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Util\Type\DateTime;
use CSocNetGroup;

class PrepareDates implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		$this->checkDatePlan($fields);
		$this->checkDatesInProject($fields);
		$this->checkItemLinked($fields, $fullTaskData);

		return $fields;
	}

	private function checkItemLinked(array $fields, array $fullTaskData): void
	{
		$endDate = ($fields['END_DATE_PLAN'] ?? null);

		// you are not allowed to clear up END_DATE_PLAN while the task is linked
		if (!isset($endDate))
		{
			return;
		}

		if ((string)$endDate !== '' && $endDate !== 0)
		{
			return;
		}

		if (!ProjectDependenceTable::checkItemLinked((int)$fullTaskData['ID']))
		{
			return;
		}

		throw new TaskFieldValidateException(Loc::getMessage('TASKS_IS_LINKED_END_DATE_PLAN_REMOVE'));

	}

	private function checkDatePlan(array $fields): void
	{
		$startDate = (string)($fields['START_DATE_PLAN'] ?? '');
		$endDate = (string)($fields['END_DATE_PLAN'] ?? '');

		if (!empty($startDate) && !empty($endDate))
		{
			$startDateTs = MakeTimeStamp($startDate);
			$endDateTs = MakeTimeStamp($endDate);

			if ($startDateTs > 0 && $endDateTs > 0)
			{
				if ($endDateTs < $startDateTs)
				{
					throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_PLAN_DATES'));
				}
				if ($endDateTs - $startDateTs > \CTasks::MAX_INT)
				{
					throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_DURATION'));
				}
			}
		}
	}

	private function checkDatesInProject(array $fields): void
	{
		$groupId = (int)($fields['GROUP_ID'] ?? 0);

		if ($groupId <= 0)
		{
			return;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$isProject = GroupProvider::isProject($groupId);
		if (!$isProject)
		{
			return;
		}

		$project = CSocNetGroup::getById($groupId);

		$projectStartDate = DateTime::createFrom($project['PROJECT_DATE_START']);
		$projectFinishDate = DateTime::createFrom($project['PROJECT_DATE_FINISH']);

		if ($projectFinishDate)
		{
			$projectFinishDate->addSecond(86399);
		}

		$deadline = null;
		$endDatePlan = null;
		$startDatePlan = null;

		if (isset($fields['DEADLINE']) && $fields['DEADLINE'])
		{
			$deadline = DateTime::createFrom($fields['DEADLINE']);
		}
		if (isset($fields['END_DATE_PLAN']) && $fields['END_DATE_PLAN'])
		{
			$endDatePlan = DateTime::createFrom($fields['END_DATE_PLAN']);
		}
		if (isset($fields['START_DATE_PLAN']) && $fields['START_DATE_PLAN'])
		{
			$startDatePlan = DateTime::createFrom($fields['START_DATE_PLAN']);
		}

		if ($deadline && !$deadline->checkInRange($projectStartDate, $projectFinishDate))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_DEADLINE_OUT_OF_PROJECT_RANGE'));
		}

		if ($endDatePlan && !$endDatePlan->checkInRange($projectStartDate, $projectFinishDate))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_PLAN_DATE_END_OUT_OF_PROJECT_RANGE'));
		}

		if ($startDatePlan && !$startDatePlan->checkInRange($projectStartDate, $projectFinishDate))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_PLAN_DATE_START_OUT_OF_PROJECT_RANGE'));
		}
	}
}