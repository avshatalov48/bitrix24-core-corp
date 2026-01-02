<?php

declare(strict_types=1);

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Timeline\Trait\ActivityComplete;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;

class Booking extends Base
{
	use ActivityComplete;

	public const ACTION_NAME_COMPLETE_WITH_STATUS = 'crm.timeline.booking.completeWithStatus';

	protected function getRequiredModules() : array
	{
		return ['booking'];
	}

	public function completeWithStatusAction(
		int $activityId,
		int $ownerTypeId,
		int $ownerId,
		string $status,
	): bool
	{
		if (!Container::getInstance()->getUserPermissions()->item()->canUpdate($ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return false;
		}

		$activityCompleted = $this->completeActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activityCompleted)
		{
			$this->addError(new Error('Error while trying to complete activity'));

			return false;
		}

		$statusUpdated = Activity\Provider\Booking\Booking::updateActivityCompleteStatus($activityId, $status);
		if (!$statusUpdated)
		{
			$this->addError(new Error('Error while trying to update booking status'));
		}

		return $statusUpdated;
	}
}
