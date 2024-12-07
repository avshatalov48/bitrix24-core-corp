<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity;

use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Entity\MessageBuilder\ProcessEntity;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

abstract class Notification
{
	public const NOTIFY_EVENT = '';
	public const ADD_SENDING_TYPE = 'sendAboutAdd';
	public const UPDATE_SENDING_TYPE = 'sendAboutUpdate';
	public const SENDING_TYPES = [
		self::ADD_SENDING_TYPE,
		self::UPDATE_SENDING_TYPE,
	];

	protected const NOTIFY_MODULE = 'crm';

	protected ProcessEntity $messageBuilder;

	public function __construct(
		protected int $entityTypeId,
		protected ?Difference $difference,
		protected ?string $sendingType,
	)
	{
		$this->messageBuilder = $this->getMessageBuilder();
	}

	final public function send(): void
	{
		if (!$this->canSend())
		{
			return;
		}

		$fromUserId = $this->getFromUserId();
		if ($fromUserId === null)
		{
			return;
		}

		$notifyDto = $this->getDefaultPreparedNotify()
			->setFromUserId($fromUserId)
		;

		if (!$fromUserId)
		{
			$notifyDto->setNotifyType(IM_NOTIFY_SYSTEM);
		}

		$receivers = $this->getReceivers();
		foreach ($receivers as $receiver)
		{
			if ($fromUserId === $receiver->getId())
			{
				continue;
			}

			$notifyDto = $notifyDto
				->setNotifyMessage($this->getNotifyMessage($receiver->getMessageType()))
				->setNotifyMessageOut($this->getNotifyMessageOut($receiver->getMessageType()))
				->setToUserId($receiver->getId())
			;

			\CIMNotify::Add($notifyDto->toArray());
		}
	}

	protected function canSend(): bool
	{
		return
			$this->isValidDifference()
			&& $this->isValidSendingType()
			&& $this->isModulesIncluded()
			&& ($this->entityTypeId !== \CCrmOwnerType::Lead || LeadSettings::isEnabled())
		;
	}

	protected function isValidDifference(): bool
	{
		if ($this->difference === null)
		{
			return false;
		}

		$neededFields = [Item::FIELD_NAME_ID, $this->getTitleFieldName()];
		foreach ($neededFields as $neededField)
		{
			if ($this->difference->getCurrentValue($neededField) === null)
			{
				return false;
			}
		}

		return true;
	}

	protected function isValidSendingType(): bool
	{
		return in_array($this->sendingType, static::SENDING_TYPES, true);
	}

	protected function isModulesIncluded(): bool
	{
		return Loader::includeModule('im');
	}

	protected function getDefaultPreparedNotify(): CIMNotifyDTO
	{
		return (new CIMNotifyDTO())
			->setMessageType(IM_MESSAGE_SYSTEM)
			->setNotifyType(IM_NOTIFY_FROM)
			->setNotifyModule(static::NOTIFY_MODULE)
			->setNotifyEvent(static::NOTIFY_EVENT)
			->setNotifyTag($this->getNotifyTag())
		;
	}

	protected function getFromUserId(): ?int
	{
		return match($this->sendingType)
		{
			static::UPDATE_SENDING_TYPE => $this->difference->getCurrentValue(Item::FIELD_NAME_UPDATED_BY),
			static::ADD_SENDING_TYPE => $this->difference->getCurrentValue(Item::FIELD_NAME_CREATED_BY),
			default => null,
		};
	}

	/**
	 * @return Receiver[]
	 */
	protected function getReceivers(): array
	{
		return match($this->sendingType)
		{
			static::UPDATE_SENDING_TYPE => $this->getReceiversWhenUpdating(),
			static::ADD_SENDING_TYPE => $this->getReceiversWhenAdding(),
			default => [],
		};
	}

	/**
	 * @return Receiver[]
	 */
	abstract protected function getReceiversWhenAdding(): array;

	/**
	 * @return Receiver[]
	 */
	abstract protected function getReceiversWhenUpdating(): array;

	abstract protected function getMessageBuilder(): ProcessEntity;

	protected function getMessage(?string $type, ?string $url = null): callable
	{
		if ($type !== null)
		{
			$this->messageBuilder->setType($type);
		}

		return $this->messageBuilder
			->getMessageCallback([
				'#TITLE#' => htmlspecialcharsbx(
					$this->difference->getCurrentValue($this->getTitleFieldName()),
				),
				'#URL#' => $url ?? '',
			])
		;
	}

	protected function getTitleFieldName(): string
	{
		return $this->entityTypeId === \CCrmOwnerType::Contact
			? Item::FIELD_NAME_FULL_NAME
			: Item::FIELD_NAME_TITLE
		;
	}

	protected function getNotifyMessage(?string $type): callable
	{
		return $this->getMessage($type, $this->getUrl());
	}

	protected function getNotifyMessageOut(?string $type): callable
	{
		return $this->getMessage($type, $this->getAbsoluteUrl());
	}

	protected function getUrl(): Uri
	{
		return Container::getInstance()->getRouter()->getItemDetailUrl(
			$this->entityTypeId,
			$this->difference->getCurrentValue(Item::FIELD_NAME_ID),
		);
	}

	protected function getAbsoluteUrl(): Uri
	{
		$url = $this->getUrl();
		$host = Application::getInstance()->getContext()->getRequest()->getServer()->getHttpHost();

		return $url->setHost($host);
	}

	protected function fillReceivers(array &$receivers, array $fillingIds, string $fillingMessageType): void
	{
		foreach ($fillingIds as $fillingId)
		{
			$receivers[] = new Receiver($fillingId, $fillingMessageType);
		}
	}

	public function setDifference(Difference $difference): static
	{
		$this->difference = $difference;

		return $this;
	}

	public function setSendingType(string $sendingType): static
	{
		$this->sendingType = $sendingType;

		return $this;
	}
}
