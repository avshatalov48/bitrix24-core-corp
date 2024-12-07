<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity;

use Bitrix\Crm\Activity\Entity\ToDo;
use Bitrix\Crm\Entity\MessageBuilder\ProcessToDoActivityResponsible;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

final class ToDoResponsibleNotification
{
	protected const MODULE_ID = 'crm';
	protected const NOTIFY_EVENT = 'changeAssignedBy';
	protected const NOTIFY_TAG_PLACEHOLDER = 'CRM|TODO_ACTIVITY|#ACTIVITY_ID#';

	public function __construct(
		protected ToDo $todo,
		protected ProcessToDoActivityResponsible $messageBuilder,
	)
	{
	}

	public function sendWhenAdd(int $fromUserId, int $toUserId): void
	{
		if (
			$fromUserId === $toUserId
			|| !$this->canSend()
		)
		{
			return;
		}

		$message = $this->getNotifyMessage($this->messageBuilder::BECOME);
		$messageOut = $this->getNotifyMessageOut($this->messageBuilder::BECOME);

		$notifyData = $this->getDefaultNotifyData()
			->setFromUserId($fromUserId)
			->setToUserId($toUserId)
			->setNotifyMessage($message)
			->setNotifyMessageOut($messageOut)
		;

		\CIMNotify::Add($notifyData->toArray());
	}

	public function sendWhenUpdate(
		int $fromUserId,
		int $currentResponsibleId,
		int $previousResponsibleId,
	): void
	{
		if (
			$previousResponsibleId === $currentResponsibleId
			|| !$this->canSend()
		)
		{
			return;
		}

		$receivers = [
			new Receiver($currentResponsibleId, $this->messageBuilder::BECOME),
			new Receiver($previousResponsibleId, $this->messageBuilder::NO_LONGER),
		];

		foreach ($receivers as $receiver)
		{
			$message = $this->getNotifyMessage($receiver->getMessageType());
			$messageOut = $this->getNotifyMessageOut($receiver->getMessageType());

			$notifyData = $this->getDefaultNotifyData()
				->setFromUserId($fromUserId)
				->setToUserId($receiver->getId())
				->setNotifyMessage($message)
				->setNotifyMessageOut($messageOut)
			;

			\CIMNotify::Add($notifyData->toArray());
		}
	}

	private function canSend(): bool
	{
		return Loader::includeModule('im');
	}

	private function getDefaultNotifyData(): CIMNotifyDTO
	{
		return (new CIMNotifyDTO())
			->setMessageType(IM_MESSAGE_SYSTEM)
			->setNotifyType(IM_NOTIFY_FROM)
			->setNotifyModule(self::MODULE_ID)
			->setNotifyEvent(self::NOTIFY_EVENT)
			->setNotifyTag($this->getNotifyTag())
		;
	}

	private function getNotifyTag(): string
	{
		return str_replace('#ACTIVITY_ID#', self::NOTIFY_TAG_PLACEHOLDER, $this->todo->getId());
	}

	private function getNotifyMessage(string $messageType): callable
	{
		return $this->getMessageCallback($messageType);
	}

	private function getNotifyMessageOut(string $messageType): callable
	{
		return function (?string $languageId = null) use ($messageType)
		{
			$getMessageCallback = $this->getMessageCallback($messageType);
			if (!($getMessageCallback instanceof \Closure))
			{
				return null;
			}

			$message = $getMessageCallback($languageId);

			// remove link in message for MESSAGE_OUT
			return strip_tags($message);
		};
	}

	private function getMessageCallback(string $messageType): callable
	{
		[ $type, $replace ] = $this->getMessageBuilderData($messageType);

		return $this->messageBuilder
			->setType($type)
			->getMessageCallback($replace)
		;
	}

	private function getMessageBuilderData(string $messageType): array
	{
		$subject = $this->getToDoTitle();
		$entityName = $this->getOwnerTitle();

		$isEmptySubject = $subject === '';
		$isEmptyEntityName = $entityName === null;

		/**
		 * Under certain conditions, we change the message type to:
		 * @see ProcessToDoActivityResponsible::BECOME_EX
		 * @see ProcessToDoActivityResponsible::BECOME_EMPTY_SUBJECT
		 * @see ProcessToDoActivityResponsible::NO_LONGER_EX
		 * @see ProcessToDoActivityResponsible::NO_LONGER_EMPTY_SUBJECT
		 */
		$messageType = match(true){
			$isEmptySubject => "{$messageType}_EMPTY_SUBJECT",
			$isEmptyEntityName => "{$messageType}_EX",
			default => $messageType,
		};

		$replace = match(true){
			$isEmptySubject => [
				'#TODO_ID#' => $this->todo->getId(),
			],
			$isEmptyEntityName => [
				'#SUBJECT#' => htmlspecialcharsbx(trim($subject)),
			],
			default => [
				'#SUBJECT#' => htmlspecialcharsbx(trim($subject)),
				'#ENTITY_TITLE#' => htmlspecialcharsbx($entityName),
			],
		};

		$replace['#URL#'] = $this->getOwnerUrl();

		return [$messageType, $replace];
	}

	private function getOwnerTitle(): ?string
	{
		$owner = $this->todo->getOwner();

		if (!CCrmOwnerType::isUseFactoryBasedApproach($owner->getEntityTypeId()))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($owner->getEntityTypeId());
		if ($factory)
		{
			$item = $factory->getItem($owner->getEntityId());
			if ($item)
			{
				return trim($item->getHeading());
			}
		}

		return null;
	}

	private function getToDoTitle(): string
	{
		$todo = [
			'SUBJECT' => $this->todo->getSubject(),
			'COMPLETED' => 'N',
		];

		return \Bitrix\Crm\Activity\Provider\ToDo\ToDo::getActivityTitle($todo);
	}

	private function getOwnerUrl(): ?Uri
	{
		return Container::getInstance()->getRouter()->getItemDetailUrl(
			$this->todo->getOwner()->getEntityTypeId(),
			$this->todo->getOwner()->getEntityId(),
		);
	}
}
