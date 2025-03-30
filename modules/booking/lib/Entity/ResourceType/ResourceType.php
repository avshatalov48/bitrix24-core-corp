<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ResourceType;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Internals\Service\Notifications\NotificationTemplateType;

class ResourceType implements EntityInterface
{
	public const INTERNAL_MODULE_ID = 'booking';

	private int|null $id = null;
	private string|null $moduleId = null;
	private string|null $code = null;
	private string|null $name = null;

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

	public function __construct()
	{
		$this->templateTypeInfo = NotificationTemplateType::Inanimate->value;
		$this->templateTypeConfirmation = NotificationTemplateType::Inanimate->value;
		$this->templateTypeReminder = NotificationTemplateType::Base->value;
		$this->templateTypeFeedback = NotificationTemplateType::Inanimate->value;
		$this->templateTypeDelayed = NotificationTemplateType::Inanimate->value;
	}

	public function getId(): int|null
	{
		return $this->id;
	}

	public function setId(int|null $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getModuleId(): string|null
	{
		return $this->moduleId;
	}

	public function setModuleId(string|null $moduleId): self
	{
		$this->moduleId = $moduleId;

		return $this;
	}

	public function getCode(): string|null
	{
		return $this->code;
	}

	public function setCode(string|null $code): self
	{
		$this->code = $code;

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

	public function setTemplateTypeDelayed(string $templateTypeDelayed): self
	{
		$this->templateTypeDelayed = $templateTypeDelayed;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'moduleId' => $this->moduleId,
			'code' => $this->code,
			'name' => $this->name,
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
		];
	}

	public static function mapFromArray(array $props): self
	{
		$resourceType = (new ResourceType())
			->setId(isset($props['id']) ? (int)$props['id'] : null)
			->setModuleId(isset($props['moduleId']) ? (string)$props['moduleId'] : null)
			->setCode(isset($props['code']) ? (string)$props['code'] : null)
			->setName(isset($props['name']) ? (string)$props['name'] : null)
		;

		if (isset($props['isInfoNotificationOn']))
		{
			$resourceType->setIsInfoNotificationOn((bool)$props['isInfoNotificationOn']);
		}
		if (isset($props['templateTypeInfo']))
		{
			$resourceType->setTemplateTypeInfo((string)$props['templateTypeInfo']);
		}

		if (isset($props['isConfirmationNotificationOn']))
		{
			$resourceType->setIsConfirmationNotificationOn((bool)$props['isConfirmationNotificationOn']);
		}
		if (isset($props['templateTypeConfirmation']))
		{
			$resourceType->setTemplateTypeConfirmation((string)$props['templateTypeConfirmation']);
		}

		if (isset($props['isReminderNotificationOn']))
		{
			$resourceType->setIsReminderNotificationOn((bool)$props['isReminderNotificationOn']);
		}
		if (isset($props['templateTypeReminder']))
		{
			$resourceType->setTemplateTypeReminder((string)$props['templateTypeReminder']);
		}

		if (isset($props['isFeedbackNotificationOn']))
		{
			$resourceType->setIsFeedbackNotificationOn((bool)$props['isFeedbackNotificationOn']);
		}
		if (isset($props['templateTypeFeedback']))
		{
			$resourceType->setTemplateTypeFeedback((string)$props['templateTypeFeedback']);
		}

		if (isset($props['isDelayedNotificationOn']))
		{
			$resourceType->setIsDelayedNotificationOn((bool)$props['isDelayedNotificationOn']);
		}
		if (isset($props['templateTypeDelayed']))
		{
			$resourceType->setTemplateTypeDelayed((string)$props['templateTypeDelayed']);
		}

		return $resourceType;
	}
}
