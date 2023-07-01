<?php

namespace Bitrix\Crm\Integration\Calendar;

use Bitrix\Crm\Service\Container;

class ActivityHandler
{
	public const SHARING_STATUS_CANCELED_BY_MANAGER = 'CANCELED_BY_MANAGER';
	public const SHARING_STATUS_CANCELED_BY_CLIENT = 'CANCELED_BY_CLIENT';
	public const SHARING_STATUS_MEETING_NOT_HELD = 'MEETING_NOT_HELD';

	private array $activity;
	private int $ownerTypeId;
	private int $ownerId;

	/**
	 * @param array $activity
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 */
	public function __construct(array $activity, int $ownerTypeId, int $ownerId)
	{
		$this->activity = $activity;
		$this->ownerTypeId = $ownerTypeId;
		$this->ownerId = $ownerId;
	}

	/**
	 * completes the crm deal calendar sharing activity
	 * @param string|null $status
	 * @return bool
	 */
	public function completeWithStatus(?string $status = null): bool
	{
		if (
			!\CCrmActivity::CheckCompletePermission(
				$this->ownerTypeId,
				$this->ownerId,
				Container::getInstance()->getUserPermissions()->getCrmPermissions(),
				['FIELDS' => $this->activity]
			)
		)
		{
			if (!isset($status) || $status !== self::SHARING_STATUS_CANCELED_BY_CLIENT)
			{
				return false;
			}
		}

		if (!\CCrmActivity::Complete($this->activity['ID'], true, ['REGISTER_SONET_EVENT' => true]))
		{
			return false;
		}

		if ($status && in_array($status, $this->getAcceptedStatuses(), true))
		{
			$this->setCompletedStatus($status);
		}

		return true;
	}

	/**
	 * @param string $status
	 * @return bool
	 */
	private function setCompletedStatus(string $status): bool
	{
		$settings = $this->activity['SETTINGS'];
		$settings[$status] = true;

		return \CCrmActivity::Update($this->activity['ID'], ['SETTINGS' => $settings]);
	}

	/**
	 * @return string[]
	 */
	private function getAcceptedStatuses(): array
	{
		return [
			self::SHARING_STATUS_CANCELED_BY_MANAGER,
			self::SHARING_STATUS_CANCELED_BY_CLIENT,
			self::SHARING_STATUS_MEETING_NOT_HELD,
		];
	}
}