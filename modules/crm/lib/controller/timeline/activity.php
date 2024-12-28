<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Timeline\trait\ActivityLoader;
use Bitrix\Crm\Controller\Timeline\trait\ActivityPermissionsChecker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use CCrmActivity;

class Activity extends Base
{
	use ActivityLoader;
	use ActivityPermissionsChecker;

	public function completeAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if (
			!CCrmActivity::CheckCompletePermission(
				$ownerTypeId,
				$ownerId,
				Container::getInstance()->getUserPermissions()->getCrmPermissions(),
				['FIELDS' => $activity]
			)
		)
		{
			$provider = CCrmActivity::GetActivityProvider($activity);
			$error = is_null($provider)
				? ErrorCode::getAccessDeniedError()
				: $provider::getCompletionDeniedError()
			;

			$this->addError($error);

			return;
		}

		$result = CCrmActivity::Complete(
			$activityId,
			true,
			[
				'REGISTER_SONET_EVENT' => true,
				'EXECUTOR_ID' => $this->getCurrentUser()?->getId(),
			]
		);

		if (!$result)
		{
			$this->addError(
				new Error(
					implode(', ', CCrmActivity::GetErrorMessages()),
					'CAN_NOT_COMPLETE'
				)
			);
		}
	}

	public function postponeAction(int $activityId, int $ownerTypeId, int $ownerId, int $offset): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if (!$this->isUpdateEnable($ownerTypeId, $ownerId))
		{
			return;
		}

		if ($offset <= 0)
		{
			$this->addError(
				new Error(
					'Offset must be greater than zero',
					'WRONG_OFFSET'
				)
			);

			return;
		}

		if (!CCrmActivity::Postpone($activityId, $offset, ['FIELDS' => $activity]))
		{
			$this->addError(
				new Error(
					implode(', ', CCrmActivity::GetErrorMessages()),
					'CAN_NOT_POSTPONE'
				)
			);
		}
	}

	public function setDeadlineAction(int $activityId, int $ownerTypeId, int $ownerId, string $value): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if (!$this->isUpdateEnable($ownerTypeId, $ownerId))
		{
			return;
		}

		$deadline = $this->prepareDatetime($value);
		if (!$deadline)
		{
			return;
		}

		CCrmActivity::PostponeToDate($activity, $deadline, true);
	}

	public function deleteAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		if (!$this->loadActivity($activityId, $ownerTypeId, $ownerId))
		{
			return;
		}

		if (!$this->isUpdateEnable($ownerTypeId, $ownerId))
		{
			return;
		}

		if (!CCrmActivity::Delete($activityId))
		{
			$this->addError(
				new Error(
					implode(', ', CCrmActivity::GetErrorMessages()),
					'CAN_NOT_DELETE')
			);
		}
	}

	public function deleteTagAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if (!$this->isUpdateEnable($ownerTypeId, $ownerId))
		{
			return;
		}

		unset($activity['SETTINGS']['TAGS']);

		if (!CCrmActivity::Update($activity['ID'], $activity))
		{
			$this->addError(
				new Error(
					implode(', ', CCrmActivity::GetErrorMessages()),
					'CAN_NOT_UPDATE')
			);
		}
	}
}
