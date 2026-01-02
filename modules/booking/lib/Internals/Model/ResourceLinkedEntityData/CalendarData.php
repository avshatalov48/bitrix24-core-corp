<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model\ResourceLinkedEntityData;

class CalendarData implements ResourceLinkedEntityDataInterface
{
	/** @var int[] */
	private array $userIds = [];
	private int|null $locationId = null;
	private bool $checkAvailability = false;
	/** @var CalendarDataRemindersDto[] */
	private array|null $reminders = null;

	public static function mapFromArray(array $props): static
	{
		return (new self)
			->setUserIds($props['userIds'] ?? [])
			->setLocationId($props['locationId'] ?? null)
			->setCheckAvailability($props['checkAvailability'] ?? false)
			->setReminders(
				($props['reminders'] ?? null) !== null
					? array_map(
					static fn(array $reminder) => new CalendarDataRemindersDto($reminder['type'], $reminder['count']),
					$props['reminders']
				)
					: null
			);
	}

	public function toArray(): array
	{
		return [
			'userIds' => $this->userIds,
			'locationId' => $this->locationId,
			'checkAvailability' => $this->checkAvailability,
			'reminders' => $this->reminders !== null
				? array_map(
					static fn(CalendarDataRemindersDto $reminder) => $reminder->toArray(),
					$this->reminders
				)
				: null,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	/**
	 * @return int[]
	 */
	public function getUserIds(): array
	{
		return $this->userIds;
	}

	public function setUserIds(array $userIds): self
	{
		$this->userIds = $userIds;

		return $this;
	}

	public function getLocationId(): ?int
	{
		return $this->locationId;
	}

	public function setLocationId(?int $locationId): self
	{
		$this->locationId = $locationId;

		return $this;
	}

	public function getCheckAvailability(): bool
	{
		return $this->checkAvailability;
	}

	public function setCheckAvailability(bool $checkAvailability): self
	{
		$this->checkAvailability = $checkAvailability;

		return $this;
	}

	/**
	 * @return CalendarDataRemindersDto[]|null
	 */
	public function getReminders(): array|null
	{
		return $this->reminders;
	}

	public function setReminders(array|null $reminders): self
	{
		$this->reminders = $reminders;

		return $this;
	}
}
