<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity\Notification;

use Bitrix\Crm\Entity\MessageBuilder\ProcessEntity;
use Bitrix\Crm\Entity\MessageBuilder\ProcessEntityObserver;
use Bitrix\Crm\Integration\Im\ProcessEntity\Notification;
use Bitrix\Crm\Integration\Im\ProcessEntity\Receiver;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

class Observer extends Notification
{
	public const NOTIFY_EVENT = 'changeObserver';

	protected function canSend(): bool
	{
		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		if ($factory === null)
		{
			return false;
		}

		return parent::canSend() && $factory->isObserversEnabled();
	}

	protected function getNotifyTag(): string
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeId);

		return "CRM|{$entityTypeName}_OBSERVER|" . $this->difference->getCurrentValue(Item::FIELD_NAME_ID);
	}

	protected function getMessageBuilder(): ProcessEntity
	{
		return new ProcessEntityObserver($this->entityTypeId);
	}

	protected function getReceiversWhenAdding(): array
	{
		$receivers = [];

		$observerIds = $this->difference->getCurrentValue(Item::FIELD_NAME_OBSERVERS) ?? [];
		foreach ($observerIds as $observerId)
		{
			$receivers[] = new Receiver($observerId, ProcessEntityObserver::BECOME_OBSERVER);
		}

		return $receivers;
	}

	protected function getReceiversWhenUpdating(): array
	{
		$beforeSaveObserverIds = $this->difference->getPreviousValue(Item::FIELD_NAME_OBSERVERS) ?? [];
		$currentObserverIds = $this->difference->getCurrentValue(Item::FIELD_NAME_OBSERVERS) ?? [];

		$receivers = [];

		$addedObserverIds = array_diff($currentObserverIds, $beforeSaveObserverIds);
		$this->fillReceivers(
			$receivers,
			$addedObserverIds,
			ProcessEntityObserver::BECOME_OBSERVER,
		);

		$removedObserverIds = array_diff($beforeSaveObserverIds, $currentObserverIds);
		$this->fillReceivers(
			$receivers,
			$removedObserverIds,
			ProcessEntityObserver::NO_LONGER_OBSERVER,
		);

		return $receivers;
	}
}
