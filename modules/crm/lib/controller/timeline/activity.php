<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;

class Activity extends \Bitrix\Crm\Controller\Base
{
	public function completeAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if(!\CCrmActivity::CheckCompletePermission(
			$ownerTypeId,
			$ownerId,
			Container::getInstance()->getUserPermissions()->getCrmPermissions(),
			['FIELDS' => $activity]
		))
		{
			$provider = \CCrmActivity::GetActivityProvider($activity);
			$error = is_null($provider) ? ErrorCode::getAccessDeniedError() : $provider::getCompletionDeniedError();
			$this->addError($error);

			return;
		}

		if (!\CCrmActivity::Complete($activityId, true, ['REGISTER_SONET_EVENT' => true]))
		{
			$this->addError(new Error(implode(', ', \CCrmActivity::GetErrorMessages()), 'CAN_NOT_COMPLETE'));
		}
	}

	public function postponeAction(int $activityId, int $ownerTypeId, int $ownerId, int $offset): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if(!\CCrmActivity::CheckUpdatePermission($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return;
		}

		if ($offset <= 0)
		{
			$this->addError(new Error('Offset must be greater than zero', 'WRONG_OFFSET'));

			return;
		}

		if (!\CCrmActivity::Postpone($activityId, $offset, ['FIELDS' => $activity]))
		{
			$this->addError(new Error(implode(', ', \CCrmActivity::GetErrorMessages()), 'CAN_NOT_POSTPONE'));
		}
	}

	public function setDeadlineAction(int $activityId, int $ownerTypeId, int $ownerId, string $value): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if(!\CCrmActivity::CheckUpdatePermission($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return;
		}
		$deadline = $this->prepareDatetime($value);
		if (!$deadline)
		{
			return;
		}

		\CCrmActivity::PostponeToDate($activity, $deadline, true);
	}

	public function deleteAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		if (!$this->loadActivity($activityId, $ownerTypeId, $ownerId))
		{
			return;
		}

		if(!\CCrmActivity::CheckUpdatePermission($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return;
		}

		if (!\CCrmActivity::Delete($activityId))
		{
			$this->addError(new Error(implode(', ', \CCrmActivity::GetErrorMessages()), 'CAN_NOT_DELETE'));
		}
	}

	private function loadActivity(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		if(!\CCrmOwnerType::IsDefined($ownerTypeId) || $ownerId <= 0)
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getOwnerNotFoundError());

			return null;
		}

		$activity = \CCrmActivity::GetByID($activityId);
		if (!$activity)
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

			return null;
		}

		return $activity;
	}
}