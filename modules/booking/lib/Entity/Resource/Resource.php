<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\Enum\Notification\ReminderNotificationDelay;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeConfirmation;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeDelayed;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeFeedback;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeInfo;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeReminder;
use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;

class Resource implements EntityInterface
{
	private int|null $id = null;
	private int|null $externalId = null;
	private ResourceType|null $type = null;
	private string|null $name = null;
	private string|null $description = null;
	private RangeCollection $slotRanges;
	private int $counter = 0;
	private bool|null $isMain = null;
	private bool|null $isDeleted = null;

	private bool $isInfoNotificationOn = true;
	private int $infoNotificationDelay = 0;
	private string $templateTypeInfo;
	private bool $isConfirmationNotificationOn = true;
	private string $templateTypeConfirmation;
	private int $confirmationNotificationDelay = 86400; // one day
	private int $confirmationNotificationRepetitions = 0;
	private int $confirmationNotificationRepetitionsInterval = 10800; // 3 hours
	private int $confirmationCounterDelay = 10800; // 3 hours
	private bool $isReminderNotificationOn = true;
	private string $templateTypeReminder;
	private int $reminderNotificationDelay;
	private bool $isFeedbackNotificationOn = true;
	private string $templateTypeFeedback;
	private bool $isDelayedNotificationOn = true;
	private string $templateTypeDelayed;
	private int $delayedNotificationDelay = 300; // 5 minutes
	private int $delayedCounterDelay = 300; // 5 minutes

	private int|null $createdBy = null;
	private int|null $createdAt = null;
	private int|null $updatedAt = null;
	private int|null $deletedAt = null;
	private ResourceLinkedEntityCollection|null $entityCollection = null;

	public function __construct()
	{
		$this->slotRanges = new RangeCollection(...[]);

		$this->templateTypeInfo = TemplateTypeInfo::InAnimate->value;
		$this->templateTypeConfirmation = TemplateTypeConfirmation::InAnimate->value;
		$this->templateTypeReminder = TemplateTypeReminder::Base->value;
		$this->templateTypeFeedback = TemplateTypeFeedback::InAnimate->value;
		$this->templateTypeDelayed = TemplateTypeDelayed::InAnimate->value;

		$this->reminderNotificationDelay = ReminderNotificationDelay::Morning->value;

		$this->entityCollection = new ResourceLinkedEntityCollection();
	}

	public function getId(): int|null
	{
		return $this->id;
	}

	public function setId(int|null $id): Resource
	{
		$this->id = $id;

		return $this;
	}

	public function getExternalId(): int|null
	{
		return $this->externalId;
	}

	public function setExternalId(int|null $externalId): Resource
	{
		$this->externalId = $externalId;

		return $this;
	}

	public function getType(): ResourceType|null
	{
		return $this->type;
	}

	public function setType(ResourceType|null $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	public function setDescription(string|null $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function getSlotRanges(): RangeCollection
	{
		return $this->slotRanges;
	}

	public function setSlotRanges(RangeCollection $slotRanges): self
	{
		$this->slotRanges = $slotRanges;

		return $this;
	}

	public function isInfoNotificationOn(): bool
	{
		return $this->isInfoNotificationOn;
	}

	public function setIsInfoNotificationOn(bool $isInfoNotificationOn): self
	{
		$this->isInfoNotificationOn = $isInfoNotificationOn;

		return $this;
	}

	public function getInfoNotificationDelay(): int
	{
		return $this->infoNotificationDelay;
	}


	public function setInfoNotificationDelay(int $infoNotificationDelay): self
	{
		$this->infoNotificationDelay = $infoNotificationDelay;

		return $this;
	}

	public function isConfirmationNotificationOn(): bool
	{
		return $this->isConfirmationNotificationOn;
	}

	public function setIsConfirmationNotificationOn(bool $isConfirmationNotificationOn): self
	{
		$this->isConfirmationNotificationOn = $isConfirmationNotificationOn;

		return $this;
	}

	public function isReminderNotificationOn(): bool
	{
		return $this->isReminderNotificationOn;
	}

	public function setIsReminderNotificationOn(bool $isReminderNotificationOn): self
	{
		$this->isReminderNotificationOn = $isReminderNotificationOn;

		return $this;
	}

	public function isFeedbackNotificationOn(): bool
	{
		return $this->isFeedbackNotificationOn;
	}

	public function setIsFeedbackNotificationOn(bool $isFeedbackNotificationOn): self
	{
		$this->isFeedbackNotificationOn = $isFeedbackNotificationOn;

		return $this;
	}

	public function getTemplateTypeInfo(): string
	{
		return $this->templateTypeInfo;
	}

	public function setTemplateTypeInfo(string $templateTypeInfo): self
	{
		if (!TemplateTypeInfo::isValid($templateTypeInfo))
		{
			throw new InvalidArgumentException('Invalid value for templateTypeInfo');
		}

		$this->templateTypeInfo = $templateTypeInfo;

		return $this;
	}

	public function getTemplateTypeConfirmation(): string
	{
		return $this->templateTypeConfirmation;
	}

	public function setTemplateTypeConfirmation(string $templateTypeConfirmation): self
	{
		if (!TemplateTypeConfirmation::isValid($templateTypeConfirmation))
		{
			throw new InvalidArgumentException('Invalid value for templateTypeConfirmation');
		}

		$this->templateTypeConfirmation = $templateTypeConfirmation;

		return $this;
	}

	public function getConfirmationNotificationDelay(): int
	{
		return $this->confirmationNotificationDelay;
	}

	public function setConfirmationNotificationDelay(int $confirmationNotificationDelay): self
	{
		$this->confirmationNotificationDelay = $confirmationNotificationDelay;

		return $this;
	}

	public function getConfirmationNotificationRepetitions(): int
	{
		return $this->confirmationNotificationRepetitions;
	}

	public function setConfirmationNotificationRepetitions(int $confirmationNotificationRepetitions): self
	{
		$this->confirmationNotificationRepetitions = $confirmationNotificationRepetitions;

		return $this;
	}

	public function getConfirmationNotificationRepetitionsInterval(): int
	{
		return $this->confirmationNotificationRepetitionsInterval;
	}

	public function setConfirmationNotificationRepetitionsInterval(int $confirmationNotificationRepetitionsInterval): self
	{
		$this->confirmationNotificationRepetitionsInterval = $confirmationNotificationRepetitionsInterval;

		return $this;
	}

	public function getConfirmationCounterDelay(): int
	{
		return $this->confirmationCounterDelay;
	}

	public function setConfirmationCounterDelay(int $confirmationCounterDelay): self
	{
		$this->confirmationCounterDelay = $confirmationCounterDelay;

		return $this;
	}

	public function getTemplateTypeReminder(): string
	{
		return $this->templateTypeReminder;
	}

	public function setTemplateTypeReminder(string $templateTypeReminder): self
	{
		if (!TemplateTypeReminder::isValid($templateTypeReminder))
		{
			throw new InvalidArgumentException('Invalid value for templateTypeReminder');
		}

		$this->templateTypeReminder = $templateTypeReminder;

		return $this;
	}

	public function getReminderNotificationDelay(): int
	{
		return $this->reminderNotificationDelay;
	}

	public function setReminderNotificationDelay(int $reminderNotificationDelay): self
	{
		$this->reminderNotificationDelay = $reminderNotificationDelay;

		return $this;
	}

	public function getTemplateTypeFeedback(): string
	{
		return $this->templateTypeFeedback;
	}

	public function setTemplateTypeFeedback(string $templateTypeFeedback): self
	{
		if (!TemplateTypeFeedback::isValid($templateTypeFeedback))
		{
			throw new InvalidArgumentException('Invalid value for templateTypeFeedback');
		}

		$this->templateTypeFeedback = $templateTypeFeedback;

		return $this;
	}

	public function isDelayedNotificationOn(): bool
	{
		return $this->isDelayedNotificationOn;
	}

	public function setIsDelayedNotificationOn(bool $isDelayedNotificationOn): self
	{
		$this->isDelayedNotificationOn = $isDelayedNotificationOn;

		return $this;
	}

	public function getTemplateTypeDelayed(): string
	{
		return $this->templateTypeDelayed;
	}

	public function setTemplateTypeDelayed(string $templateTypeDelayed): self
	{
		if (!TemplateTypeDelayed::isValid($templateTypeDelayed))
		{
			throw new InvalidArgumentException('Invalid value for templateTypeDelayed');
		}

		$this->templateTypeDelayed = $templateTypeDelayed;

		return $this;
	}

	public function getDelayedNotificationDelay(): int
	{
		return $this->delayedNotificationDelay;
	}

	public function setDelayedNotificationDelay(int $delayedNotificationDelay): self
	{
		$this->delayedNotificationDelay = $delayedNotificationDelay;

		return $this;
	}

	public function getDelayedCounterDelay(): int
	{
		return $this->delayedCounterDelay;
	}

	public function setDelayedCounterDelay(int $delayedCounterDelay): self
	{
		$this->delayedCounterDelay = $delayedCounterDelay;

		return $this;
	}

	public function getCreatedBy(): int|null
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int|null $createdBy): self
	{
		$this->createdBy = $createdBy;

		return $this;
	}

	public function getCreatedAt(): int|null
	{
		return $this->createdAt;
	}

	public function setCreatedAt(int|null $createdAt): self
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): int|null
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(int|null $updatedAt): self
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getDeletedAt(): int|null
	{
		return $this->deletedAt;
	}

	public function setDeletedAt(int|null $deletedAt): self
	{
		$this->deletedAt = $deletedAt;

		return $this;
	}

	public function getCounter(): int
	{
		return $this->counter;
	}

	public function setCounter(int $value): self
	{
		$this->counter = $value;

		return $this;
	}

	public function isExternal(): bool
	{
		$typeHasExternalModuleId = false;
		$moduleId = $this->type?->getModuleId();

		if ($moduleId !== null && $moduleId !== 'booking')
		{
			$typeHasExternalModuleId = true;
		}

		return $this->getExternalId() && $typeHasExternalModuleId;
	}

	public function isMain(): bool|null
	{
		return $this->isMain;
	}

	public function setMain(bool $main): self
	{
		$this->isMain = $main;

		return $this;
	}

	public function isDeleted(): bool|null
	{
		return $this->isDeleted;
	}

	public function setDeleted(bool $isDeleted): self
	{
		$this->isDeleted = $isDeleted;

		return $this;
	}

	public function getEntityCollection(): ResourceLinkedEntityCollection
	{
		return $this->entityCollection;
	}

	public function setEntityCollection(ResourceLinkedEntityCollection $entityCollection): void
	{
		$this->entityCollection = $entityCollection;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'externalId' => $this->externalId,
			'type' => $this->type?->toArray(),
			'isMain' => $this->isMain,
			'isDeleted' => $this->isDeleted,
			'name' => $this->name,
			'description' => $this->description,
			'slotRanges' => $this->slotRanges->toArray(),
			'isInfoNotificationOn' => $this->isInfoNotificationOn,
			'templateTypeInfo' => $this->templateTypeInfo,
			'infoNotificationDelay' => $this->infoNotificationDelay,
			'isConfirmationNotificationOn' => $this->isConfirmationNotificationOn,
			'templateTypeConfirmation' => $this->templateTypeConfirmation,
			'confirmationNotificationDelay' => $this->confirmationNotificationDelay,
			'confirmationNotificationRepetitions' => $this->confirmationNotificationRepetitions,
			'confirmationNotificationRepetitionsInterval' => $this->confirmationNotificationRepetitionsInterval,
			'confirmationCounterDelay' => $this->confirmationCounterDelay,
			'isReminderNotificationOn' => $this->isReminderNotificationOn,
			'templateTypeReminder' => $this->templateTypeReminder,
			'reminderNotificationDelay' => $this->reminderNotificationDelay,
			'isFeedbackNotificationOn' => $this->isFeedbackNotificationOn,
			'templateTypeFeedback' => $this->templateTypeFeedback,
			'isDelayedNotificationOn' => $this->isDelayedNotificationOn,
			'templateTypeDelayed' => $this->templateTypeDelayed,
			'delayedNotificationDelay' => $this->delayedNotificationDelay,
			'delayedCounterDelay' => $this->delayedCounterDelay,
			'createdBy' => $this->createdBy,
			'createdAt' => $this->createdAt,
			'updatedAt' => $this->updatedAt,
			'deletedAt' => $this->deletedAt,
			'counter' => $this->counter,
			'entities' => $this->entityCollection->toArray(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		$type = isset($props['type']) ? ResourceType::mapFromArray($props['type']) : null;

		$slotRanges = isset($props['slotRanges'])
			? RangeCollection::mapFromArray($props['slotRanges'])
			: new RangeCollection(...[])
		;

		$resource = (new Resource())
			->setId(isset($props['id']) ? (int)$props['id'] : null)
			->setExternalId(isset($props['externalId']) ? (int)$props['externalId'] : null)
			->setType($type)
			->setName(isset($props['name']) ? (string)$props['name'] : null)
			->setDescription(isset($props['description']) ? (string)$props['description'] : null)
			->setSlotRanges($slotRanges)
			->setCreatedBy(isset($props['createdBy']) ? (int)$props['createdBy'] : null)
			->setCreatedAt(isset($props['createdAt']) ? (int)$props['createdAt'] : null)
			->setUpdatedAt(isset($props['updatedAt']) ? (int)$props['updatedAt'] : null)
			->setDeletedAt(isset($props['deletedAt']) ? (int)$props['deletedAt'] : null)
		;

		if (isset($props['isInfoNotificationOn']))
		{
			$resource->setIsInfoNotificationOn((bool)$props['isInfoNotificationOn']);
		}
		if (isset($props['templateTypeInfo']))
		{
			$resource->setTemplateTypeInfo((string)$props['templateTypeInfo']);
		}
		if (isset($props['infoNotificationDelay']))
		{
			$resource->setInfoNotificationDelay((int)$props['infoNotificationDelay']);
		}

		if (isset($props['isConfirmationNotificationOn']))
		{
			$resource->setIsConfirmationNotificationOn((bool)$props['isConfirmationNotificationOn']);
		}
		if (isset($props['templateTypeConfirmation']))
		{
			$resource->setTemplateTypeConfirmation((string)$props['templateTypeConfirmation']);
		}
		if (isset($props['confirmationNotificationDelay']))
		{
			$resource->setConfirmationNotificationDelay((int)$props['confirmationNotificationDelay']);
		}
		if (isset($props['confirmationNotificationRepetitions']))
		{
			$resource->setConfirmationNotificationRepetitions((int)$props['confirmationNotificationRepetitions']);
		}
		if (isset($props['confirmationNotificationRepetitionsInterval']))
		{
			$resource->setConfirmationNotificationRepetitionsInterval((int)$props['confirmationNotificationRepetitionsInterval']);
		}
		if (isset($props['confirmationCounterDelay']))
		{
			$resource->setConfirmationCounterDelay((int)$props['confirmationCounterDelay']);
		}

		if (isset($props['isReminderNotificationOn']))
		{
			$resource->setIsReminderNotificationOn((bool)$props['isReminderNotificationOn']);
		}
		if (isset($props['templateTypeReminder']))
		{
			$resource->setTemplateTypeReminder((string)$props['templateTypeReminder']);
		}
		if (isset($props['reminderNotificationDelay']))
		{
			$resource->setReminderNotificationDelay((int)$props['reminderNotificationDelay']);
		}

		if (isset($props['isFeedbackNotificationOn']))
		{
			$resource->setIsFeedbackNotificationOn((bool)$props['isFeedbackNotificationOn']);
		}
		if (isset($props['templateTypeFeedback']))
		{
			$resource->setTemplateTypeFeedback((string)$props['templateTypeFeedback']);
		}

		if (isset($props['isDelayedNotificationOn']))
		{
			$resource->setIsDelayedNotificationOn((bool)$props['isDelayedNotificationOn']);
		}
		if (isset($props['templateTypeDelayed']))
		{
			$resource->setTemplateTypeDelayed((string)$props['templateTypeDelayed']);
		}
		if (isset($props['delayedNotificationDelay']))
		{
			$resource->setDelayedNotificationDelay((int)$props['delayedNotificationDelay']);
		}
		if (isset($props['delayedCounterDelay']))
		{
			$resource->setDelayedCounterDelay((int)$props['delayedCounterDelay']);
		}

		if (isset($props['isMain']))
		{
			$resource->setMain((bool)$props['isMain']);
		}

		if (isset($props['isDeleted']))
		{
			$resource->setDeleted((bool)$props['isDeleted']);
		}

		if (isset($props['entities']))
		{
			$resource->setEntityCollection(ResourceLinkedEntityCollection::mapFromArray($props['entities']));
		}

		return $resource;
	}
}
