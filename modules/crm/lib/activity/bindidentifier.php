<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\ItemIdentifier;

class BindIdentifier
{
	public function __construct(
		private ItemIdentifier $ownerIdentifier,
		private int $activityId,
	)
	{
	}

	public static function create(int $entityTypeId, int $entityId, int $activityId): self
	{
		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);

		return new self($itemIdentifier, $activityId);
	}

	public function getOwnerIdentifier(): ItemIdentifier
	{
		return $this->ownerIdentifier;
	}

	public function setOwnerIdentifier(ItemIdentifier $ownerIdentifier): self
	{
		$this->ownerIdentifier = $ownerIdentifier;

		return $this;
	}

	public function getActivityId(): int
	{
		return $this->activityId;
	}

	public function setActivityId(int $activityId): self
	{
		$this->activityId = $activityId;

		return $this;
	}
}
