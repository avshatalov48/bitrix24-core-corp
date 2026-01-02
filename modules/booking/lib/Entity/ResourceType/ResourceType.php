<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ResourceType;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\Enum\Notification\ReminderNotificationDelay;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeConfirmation;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeDelayed;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeFeedback;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeInfo;
use Bitrix\Booking\Entity\Enum\TemplateType\TemplateTypeReminder;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;

class ResourceType implements EntityInterface
{
	public const INTERNAL_MODULE_ID = 'booking';

	private int|null $id = null;
	private string|null $moduleId = null;
	private string|null $code = null;
	private string|null $name = null;
	private int|null $resourcesCnt = 0;

	private bool $isInfoNotificationOn = true;
	private string $templateTypeInfo;
	private int $infoNotificationDelay = 0;
	private bool $isConfirmationNotificationOn = true;
	private string $templateTypeConfirmation;
	private int $confirmationNotificationDelay = 86400; // one day
	private int $confirmationNotificationRepetitions = 0;
	private int $confirmationNotificationRepetitionsInterval = 10800; // 3 hours
	private int $confirmationCounterDelay = 10800; // 3 hours
	private int $reminderNotificationDelay;
	private bool $isReminderNotificationOn = true;
	private string $templateTypeReminder;
	private bool $isFeedbackNotificationOn = true;
	private string $templateTypeFeedback;
	private bool $isDelayedNotificationOn = true;
	private string $templateTypeDelayed;
	private int $delayedNotificationDelay = 300; // 5 minutes
	private int $delayedCounterDelay = 300; // 5 minutes

	public function __construct()
	{
		$this->templateTypeInfo = TemplateTypeInfo::InAnimate->value;
		$this->templateTypeConfirmation = TemplateTypeConfirmation::InAnimate->value;
		$this->templateTypeReminder = TemplateTypeReminder::Base->value;
		$this->templateTypeFeedback = TemplateTypeFeedback::InAnimate->value;
		$this->templateTypeDelayed = TemplateTypeDelayed::InAnimate->value;

		$this->reminderNotificationDelay = ReminderNotificationDelay::Morning->value;
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

	public function isReminderNotificationOn(): bool
	{
		return $this->isReminderNotificationOn;
	}

	public function setIsReminderNotificationOn(bool $isReminderNotificationOn): self
	{
		$this->isReminderNotificationOn = $isReminderNotificationOn;

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

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'moduleId' => $this->moduleId,
			'code' => $this->code,
			'name' => $this->name,
			'resourcesCnt' => $this->resourcesCnt,
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

		if (isset($props['infoNotificationDelay']))
		{
			$resourceType->setInfoNotificationDelay((int)$props['infoNotificationDelay']);
		}

		if (isset($props['isConfirmationNotificationOn']))
		{
			$resourceType->setIsConfirmationNotificationOn((bool)$props['isConfirmationNotificationOn']);
		}
		if (isset($props['templateTypeConfirmation']))
		{
			$resourceType->setTemplateTypeConfirmation((string)$props['templateTypeConfirmation']);
		}
		if (isset($props['confirmationNotificationDelay']))
		{
			$resourceType->setConfirmationNotificationDelay((int)$props['confirmationNotificationDelay']);
		}
		if (isset($props['confirmationNotificationRepetitions']))
		{
			$resourceType->setConfirmationNotificationRepetitions((int)$props['confirmationNotificationRepetitions']);
		}
		if (isset($props['confirmationNotificationRepetitionsInterval']))
		{
			$resourceType->setConfirmationNotificationRepetitionsInterval((int)$props['confirmationNotificationRepetitionsInterval']);
		}
		if (isset($props['confirmationCounterDelay']))
		{
			$resourceType->setConfirmationCounterDelay((int)$props['confirmationCounterDelay']);
		}

		if (isset($props['isReminderNotificationOn']))
		{
			$resourceType->setIsReminderNotificationOn((bool)$props['isReminderNotificationOn']);
		}
		if (isset($props['templateTypeReminder']))
		{
			$resourceType->setTemplateTypeReminder((string)$props['templateTypeReminder']);
		}
		if (isset($props['reminderNotificationDelay']))
		{
			$resourceType->setReminderNotificationDelay((int)$props['reminderNotificationDelay']);
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
		if (isset($props['delayedNotificationDelay']))
		{
			$resourceType->setDelayedNotificationDelay((int)$props['delayedNotificationDelay']);
		}
		if (isset($props['delayedCounterDelay']))
		{
			$resourceType->setDelayedCounterDelay((int)$props['delayedCounterDelay']);
		}

		return $resourceType;
	}

	public function setResourcesCnt(int|null $resourcesCnt): ResourceType
	{
		$this->resourcesCnt = $resourcesCnt;

		return $this;
	}
}
