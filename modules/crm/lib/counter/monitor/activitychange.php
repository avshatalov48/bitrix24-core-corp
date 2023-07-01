<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Type\DateTime;

class ActivityChange
{
	private int $id;
	private ?bool $oldIsIncomingChannel;
	private ?bool $newIsIncomingChannel;
	private ?DateTime $oldDeadline;
	private ?DateTime $newDeadline;
	private ?bool $oldIsCompleted;
	private ?bool $newIsCompleted;
	private ?int $oldResponsibleId;
	private ?int $newResponsibleId;
	private array $oldBindings;
	private array $newBindings;
	private ?DateTime $oldLightTime;
	private ?DateTime $newLightTime;

	public static function create(
		int $id,
		array $oldFields,
		array $oldBindings,
		array $newFields,
		array $newBindings,
		?DateTime $oldLightTime,
		?DateTime $newLightTime
	): self
	{
		$oldDeadline = (isset($oldFields['DEADLINE']) && $oldFields['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($oldFields['DEADLINE']))
			? DateTime::createFromUserTime($oldFields['DEADLINE'])
			: null
		;
		$newDeadline = (isset($newFields['DEADLINE']) && $newFields['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($newFields['DEADLINE']))
			? DateTime::createFromUserTime($newFields['DEADLINE'])
			: null
		;

		$change = new self(
			$id,
			isset($oldFields['IS_INCOMING_CHANNEL']) ? $oldFields['IS_INCOMING_CHANNEL'] === 'Y' : null,
			isset($newFields['IS_INCOMING_CHANNEL']) ? $newFields['IS_INCOMING_CHANNEL'] === 'Y' : null,
			$oldDeadline,
			$newDeadline,
			isset($oldFields['COMPLETED']) ? $oldFields['COMPLETED'] === 'Y' : null,
			isset($newFields['COMPLETED']) ? $newFields['COMPLETED'] === 'Y' : null,
			isset($oldFields['RESPONSIBLE_ID']) ? (int)$oldFields['RESPONSIBLE_ID'] : null,
			isset($newFields['RESPONSIBLE_ID']) ? (int)$newFields['RESPONSIBLE_ID'] : null,
			self::prepareBindings($oldBindings),
			self::prepareBindings($newBindings),
			$oldLightTime,
			$newLightTime
		);

		return $change;
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public static function prepareBindings(array $bindings): array
	{
		$result = [];
		foreach ($bindings as $binding)
		{
			$ownerTypeId = (int)$binding['OWNER_TYPE_ID'];
			$ownerId = (int)$binding['OWNER_ID'];
			if (\CCrmOwnerType::IsDefined($ownerTypeId) && $ownerId > 0)
			{
				$result[] = new ItemIdentifier($ownerTypeId, $ownerId);
			}
		}

		return $result;
	}

	public function __construct(
		int $id,
		?bool $oldIsIncomingChannel,
		?bool $newIsIncomingChannel,
		?DateTime $oldDeadline,
		?DateTime $newDeadline,
		?bool $oldIsCompleted,
		?bool $newIsCompleted,
		?int $oldResponsibleId,
		?int $newResponsibleId,
		array $oldBindings,
		array $newBindings,
		?DateTime $oldLightTime,
		?DateTime $newLightTime
	)
	{
		$this->id = $id;
		$this->oldIsIncomingChannel = $oldIsIncomingChannel;
		$this->newIsIncomingChannel = $newIsIncomingChannel;
		$this->oldDeadline = $oldDeadline;
		$this->newDeadline = $newDeadline;
		$this->oldIsCompleted = $oldIsCompleted;
		$this->newIsCompleted = $newIsCompleted;
		$this->oldResponsibleId = $oldResponsibleId;
		$this->newResponsibleId = $newResponsibleId;
		$this->oldBindings = $oldBindings;
		$this->newBindings = $newBindings;
		$this->oldLightTime = $oldLightTime;
		$this->newLightTime = $newLightTime;
	}

	public function applyNewChange(self $activityChange): void
	{
		$this->newIsIncomingChannel = $activityChange->getNewIsIncomingChannel();
		$this->newDeadline = $activityChange->getNewDeadline();
		$this->newIsCompleted = $activityChange->getNewIsCompleted();
		$this->newBindings = $activityChange->getNewBindings();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getOldIsIncomingChannel(): ?bool
	{
		return $this->oldIsIncomingChannel;
	}

	public function getNewIsIncomingChannel(): ?bool
	{
		return $this->newIsIncomingChannel;
	}

	public function isIncomingChannelChanged(): bool
	{
		return ($this->oldIsIncomingChannel !== $this->newIsIncomingChannel);
	}

	public function getOldDeadline(): ?DateTime
	{
		return $this->oldDeadline;
	}

	public function getNewDeadline(): ?DateTime
	{
		return $this->newDeadline;
	}

	public function isDeadlineChanged(): bool
	{
		if ($this->oldDeadline && $this->newDeadline)
		{
			return $this->oldDeadline->getTimestamp() !== $this->newDeadline->getTimestamp();
		}
		if (!$this->oldDeadline && !$this->newDeadline)
		{
			return false;
		}

		return true;
	}

	public function getOldIsCompleted(): ?bool
	{
		return $this->oldIsCompleted;
	}

	public function getNewIsCompleted(): ?bool
	{
		return $this->newIsCompleted;
	}

	public function isCompletedChanged(): bool
	{
		return ($this->oldIsCompleted !== $this->newIsCompleted);
	}


	public function getOldResponsibleId(): ?int
	{
		return $this->oldResponsibleId;
	}

	public function getNewResponsibleId(): ?int
	{
		return $this->newResponsibleId;
	}

	public function isResponsibleIdChanged(): bool
	{
		return ($this->oldResponsibleId !== $this->newResponsibleId);
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function getOldBindings(): array
	{
		return $this->oldBindings;
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function getNewBindings(): array
	{
		return $this->newBindings;
	}

	public function getAffectedBindings(): array
	{
		$result = [];
		foreach ($this->getOldBindings() as $binding)
		{
			$result[$binding->getHash()] = $binding;
		}
		foreach ($this->getNewBindings() as $binding)
		{
			$result[$binding->getHash()] = $binding;
		}

		return array_values($result);
	}

	public function areBindingsChanged(): bool
	{
		if (empty($this->getOldBindings())) // assume bindings can not be changed for new activity
		{
			return false;
		}
		$oldBindingsHashes = [];
		$newBindingsHashes = [];
		foreach ($this->getOldBindings() as $binding)
		{
			$oldBindingsHashes[] = $binding->getHash();
		}
		foreach ($this->getNewBindings() as $binding)
		{
			$newBindingsHashes[] = $binding->getHash();
		}
		$removedBindings = array_diff($oldBindingsHashes, $newBindingsHashes);
		$addedBindings = array_diff($newBindingsHashes, $oldBindingsHashes);

		return !empty($removedBindings) || !empty($addedBindings);
	}

	public function getAffectedCounterTypes(): array
	{
		$isIncomingChannelChanged = $this->isIncomingChannelChanged();
		$isDeadlineChanged = $this->isDeadlineChanged();
		$isLightTimeChanged = $this->isLightTimeChanges();
		$affectedTypeIds = [];
		if (
			($isDeadlineChanged && $isIncomingChannelChanged)
			|| ($isLightTimeChanged && $isIncomingChannelChanged)
			|| $this->isCompletedChanged()
			|| $this->areBindingsChanged()
		)
		{
			$affectedTypeIds = EntityCounterType::getAll(true);
		}
		elseif ($isDeadlineChanged)
		{
			$affectedTypeIds = EntityCounterType::getAllDeadlineBased(true);
		}
		elseif ($isLightTimeChanged)
		{
			$affectedTypeIds = EntityCounterType::getAllLightTimeBased(true);
		}
		elseif ($isIncomingChannelChanged)
		{
			$affectedTypeIds = EntityCounterType::getAllIncomingBased(true);
		}

		return $affectedTypeIds;
	}

	public function wasActivityDeleted():bool
	{
		return (is_null($this->newDeadline) && is_null($this->newIsCompleted) && is_null($this->newIsIncomingChannel) && is_null($this->newResponsibleId));
	}

    public function hasSignificantChangesForCountable(): bool
    {
		return 	$this->isIncomingChannelChanged()
			|| $this->isDeadlineChanged()
			|| $this->isCompletedChanged()
			|| $this->isResponsibleIdChanged()
			|| $this->areBindingsChanged()
			|| $this->isLightTimeChanges()
		;
    }

	public function isLightTimeChanges(): bool
	{
		$oldLt = $this->oldLightTime ? $this->oldLightTime->getTimestamp() : 0;
		$newLt = $this->newLightTime ? $this->newLightTime->getTimestamp() : 0;

		return $oldLt !== $newLt;
	}

	public function getOldLightTime(): ?DateTime
	{
		return $this->oldLightTime;
	}

	public function getNewLightTime(): ?DateTime
	{
		return $this->newLightTime;
	}

}
