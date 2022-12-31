<?php

namespace Bitrix\Crm\Activity;


use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Type\DateTime;

class UncompletedActivityChange
{
	private int $id;
	private ?bool $oldIsIncomingChannel;
	private ?bool $newIsIncomingChannel;
	private ?DateTime $oldDeadline;
	private ?DateTime $newDeadline;
	private ?int $oldResponsibleId;
	private ?int $newResponsibleId;
	private ?bool $oldIsCompleted;
	private ?bool $newIsCompleted;
	private array $oldBindings;
	private array $newBindings;

	public static function create(int $id, array $oldFields, array $oldBindings, array $newFields, array $newBindings): self
	{
		$oldDeadline = ($oldFields['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($oldFields['DEADLINE']))
			? DateTime::createFromUserTime($oldFields['DEADLINE'])
			: null
		;
		$newDeadline = ($newFields['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($newFields['DEADLINE']))
			? DateTime::createFromUserTime($newFields['DEADLINE'])
			: null
		;

		return new self(
			$id,
			$oldFields['IS_INCOMING_CHANNEL'] ? ($oldFields['IS_INCOMING_CHANNEL'] === 'Y') : null,
			$newFields['IS_INCOMING_CHANNEL'] ? ($newFields['IS_INCOMING_CHANNEL'] === 'Y') : null,
			$oldDeadline,
			$newDeadline,
			$oldFields['RESPONSIBLE_ID'] ? (int)$oldFields['RESPONSIBLE_ID'] : null,
			$newFields['RESPONSIBLE_ID'] ? (int)$newFields['RESPONSIBLE_ID']: null,
			$oldFields['COMPLETED'] ? ($oldFields['COMPLETED'] === 'Y') : null,
			$newFields['COMPLETED'] ? ($newFields['COMPLETED'] === 'Y') : null,
			self::prepareBindings($oldBindings),
			self::prepareBindings($newBindings)
		);
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
		?int $oldResponsibleId,
		?int $newResponsibleId,
		?bool $oldIsCompleted,
		?bool $newIsCompleted,
		array $oldBindings,
		array $newBindings
	)
	{
		$this->id = $id;
		$this->oldIsIncomingChannel = $oldIsIncomingChannel;
		$this->newIsIncomingChannel = $newIsIncomingChannel;
		$this->oldDeadline = $oldDeadline;
		$this->newDeadline = $newDeadline;
		$this->oldResponsibleId = $oldResponsibleId;
		$this->newResponsibleId = $newResponsibleId;
		$this->oldIsCompleted = $oldIsCompleted;
		$this->newIsCompleted = $newIsCompleted;
		$this->oldBindings = $oldBindings;
		$this->newBindings = $newBindings;
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
		return $this->oldResponsibleId !== $this->newResponsibleId;
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

	public function hasChanges(): bool
	{
		return
			$this->isIncomingChannelChanged()
			|| $this->isDeadlineChanged()
			|| $this->isResponsibleIdChanged()
			|| $this->isCompletedChanged()
		;
	}

	public function wasActivityJustAdded(): bool
	{
		return is_null($this->getOldDeadline())
			&& is_null($this->getOldIsIncomingChannel())
			&& is_null($this->getOldResponsibleId())
			&& is_null($this->getOldIsCompleted())
		;
	}

	public function wasActivityJustDeleted(): bool
	{
		return is_null($this->getNewDeadline())
			&& is_null($this->getNewIsIncomingChannel())
			&& is_null($this->getNewResponsibleId())
			&& is_null($this->getNewIsCompleted())
		;
	}

	public function wasActivityJustCompleted(): bool
	{
		return $this->getOldIsCompleted()===false && $this->getNewIsCompleted();
	}

	public function wasActivityJustUnCompleted(): bool
	{
		return $this->getOldIsCompleted() && $this->getNewIsCompleted() === false;
	}
}
