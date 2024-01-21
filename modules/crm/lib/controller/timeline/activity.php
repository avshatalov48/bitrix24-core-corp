<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use CCrmActivity;
use CCrmOwnerType;

class Activity extends Base
{
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

	final protected function loadActivity(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		if ($activityId <= 0)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if (!$this->isBindingsValid($activityId, $ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		if (!CCrmActivity::CheckReadPermission($ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		if (!$activity)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return $activity;
	}

	protected function isUpdateEnable(int $ownerTypeId, int $ownerId): bool
	{
		if (!CCrmActivity::CheckUpdatePermission($ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return false;
		}

		return true;
	}

	private function isBindingsValid(int $activityId, int $ownerTypeId, int $ownerId): ?bool
	{
		if (
			$ownerId <= 0
			||!CCrmOwnerType::IsDefined($ownerTypeId)
		)
		{
			return false;
		}

		$isExistBinding = false;
		$bindingsData = CCrmActivity::GetBindings($activityId);
		foreach ($bindingsData as $binding)
		{
			if (
				(int)$binding['OWNER_TYPE_ID'] === $ownerTypeId
				&& (int)$binding['OWNER_ID'] === $ownerId
			)
			{
				$isExistBinding = true;

				break;
			}
		}

		return $isExistBinding;
	}
}
