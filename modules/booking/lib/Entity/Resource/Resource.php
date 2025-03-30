<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Service\Notifications\NotificationTemplateType;

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

	private bool $isInfoNotificationOn = true;
	private string $templateTypeInfo;
	private bool $isConfirmationNotificationOn = true;
	private string $templateTypeConfirmation;
	private bool $isReminderNotificationOn = true;
	private string $templateTypeReminder;
	private bool $isFeedbackNotificationOn = true;
	private string $templateTypeFeedback;
	private bool $isDelayedNotificationOn = true;
	private string $templateTypeDelayed;

	private int|null $createdBy = null;
	private int|null $createdAt = null;
	private int|null $updatedAt = null;

	public function __construct()
	{
		$this->slotRanges = new RangeCollection(...[]);

		$this->templateTypeInfo = NotificationTemplateType::Inanimate->value;
		$this->templateTypeConfirmation = NotificationTemplateType::Inanimate->value;
		$this->templateTypeReminder = NotificationTemplateType::Inanimate->value;
		$this->templateTypeFeedback = NotificationTemplateType::Inanimate->value;
		$this->templateTypeDelayed = NotificationTemplateType::Inanimate->value;
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
		$this->templateTypeInfo = $templateTypeInfo;

		return $this;
	}

	public function getTemplateTypeConfirmation(): string
	{
		return $this->templateTypeConfirmation;
	}

	public function setTemplateTypeConfirmation(string $templateTypeConfirmation): self
	{
		$this->templateTypeConfirmation = $templateTypeConfirmation;

		return $this;
	}

	public function getTemplateTypeReminder(): string
	{
		return $this->templateTypeReminder;
	}

	public function setTemplateTypeReminder(string $templateTypeReminder): self
	{
		$this->templateTypeReminder = $templateTypeReminder;

		return $this;
	}

	public function getTemplateTypeFeedback(): string
	{
		return $this->templateTypeFeedback;
	}

	public function setTemplateTypeFeedback(string $templateTypeFeedback): self
	{
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

	public function setTemplateTypeDelayed(string $templateTypeFeedback): self
	{
		$this->templateTypeDelayed = $templateTypeFeedback;

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

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'externalId' => $this->externalId,
			'type' => $this->type?->toArray(),
			'isMain' => $this->isMain,
			'name' => $this->name,
			'description' => $this->description,
			'slotRanges' => $this->slotRanges->toArray(),
			'isInfoNotificationOn' => $this->isInfoNotificationOn,
			'templateTypeInfo' => $this->templateTypeInfo,
			'isConfirmationNotificationOn' => $this->isConfirmationNotificationOn,
			'templateTypeConfirmation' => $this->templateTypeConfirmation,
			'isReminderNotificationOn' => $this->isReminderNotificationOn,
			'templateTypeReminder' => $this->templateTypeReminder,
			'isFeedbackNotificationOn' => $this->isFeedbackNotificationOn,
			'templateTypeFeedback' => $this->templateTypeFeedback,
			'isDelayedNotificationOn' => $this->isDelayedNotificationOn,
			'templateTypeDelayed' => $this->templateTypeDelayed,
			'createdBy' => $this->createdBy,
			'createdAt' => $this->createdAt,
			'updatedAt' => $this->updatedAt,
			'counter' => $this->counter,
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
		;

		if (isset($props['isInfoNotificationOn']))
		{
			$resource->setIsInfoNotificationOn((bool)$props['isInfoNotificationOn']);
		}
		if (isset($props['templateTypeInfo']))
		{
			$resource->setTemplateTypeInfo((string)$props['templateTypeInfo']);
		}

		if (isset($props['isConfirmationNotificationOn']))
		{
			$resource->setIsConfirmationNotificationOn((bool)$props['isConfirmationNotificationOn']);
		}
		if (isset($props['templateTypeConfirmation']))
		{
			$resource->setTemplateTypeConfirmation((string)$props['templateTypeConfirmation']);
		}

		if (isset($props['isReminderNotificationOn']))
		{
			$resource->setIsReminderNotificationOn((bool)$props['isReminderNotificationOn']);
		}
		if (isset($props['templateTypeReminder']))
		{
			$resource->setTemplateTypeReminder((string)$props['templateTypeReminder']);
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

		if (isset($props['isMain']))
		{
			$resource->setMain((bool)$props['isMain']);
		}

		return $resource;
	}
}
