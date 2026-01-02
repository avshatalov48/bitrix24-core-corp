<?php

namespace Bitrix\Crm\Controller\Timeline\Trait;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;

trait ActivityComplete
{
	use ActivityLoader;
	use ActivityPermissionsChecker;

	abstract public function getCurrentUser(): ?CurrentUser;
	abstract protected function addError(Error $error);

	protected function completeActivity(int $activityId, int $ownerTypeId, int $ownerId): bool
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return false;
		}

		if (
			!\CCrmActivity::CheckCompletePermission(
				$ownerTypeId,
				$ownerId,
				Container::getInstance()->getUserPermissions()->getCrmPermissions(),
				['FIELDS' => $activity]
			)
		)
		{
			$provider = \CCrmActivity::GetActivityProvider($activity);
			$error = is_null($provider)
				? ErrorCode::getAccessDeniedError()
				: $provider::getCompletionDeniedError()
			;

			$this->addError($error);

			return false;
		}

		return \CCrmActivity::Complete(
			$activityId,
			true,
			[
				'REGISTER_SONET_EVENT' => true,
				'EXECUTOR_ID' => $this->getCurrentUser()?->getId(),
			]
		);
	}
}
