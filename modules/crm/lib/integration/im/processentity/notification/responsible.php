<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity\Notification;

use Bitrix\Crm\Entity\MessageBuilder\ProcessEntity;
use Bitrix\Crm\Entity\MessageBuilder\ProcessEntityResponsible;
use Bitrix\Crm\Integration\Im\ProcessEntity\Notification;
use Bitrix\Crm\Integration\Im\ProcessEntity\Receiver;
use Bitrix\Crm\Item;

class Responsible extends Notification
{
	public const NOTIFY_EVENT = 'changeAssignedBy';

	protected function getNotifyTag(): string
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeId);

		return "CRM|{$entityTypeName}_RESPONSIBLE|" . $this->difference->getCurrentValue(Item::FIELD_NAME_ID);
	}

	protected function getMessageBuilder(): ProcessEntity
	{
		return new ProcessEntityResponsible($this->entityTypeId);
	}

	protected function getReceiversWhenAdding(): array
	{
		$responsibleId = $this->difference->getCurrentValue(Item::FIELD_NAME_ASSIGNED);
		if ($responsibleId === null)
		{
			return [];
		}

		return [
			new Receiver(
				(int)$responsibleId,
				ProcessEntityResponsible::BECOME_RESPONSIBLE,
			),
		];
	}

	protected function getReceiversWhenUpdating(): array
	{
		$beforeSaveResponsibleId = $this->difference->getPreviousValue(Item::FIELD_NAME_ASSIGNED);
		$currentResponsibleId = $this->difference->getCurrentValue(Item::FIELD_NAME_ASSIGNED);

		if ($beforeSaveResponsibleId === $currentResponsibleId)
		{
			return [];
		}

		$receivers = [];
		if ($currentResponsibleId !== null)
		{
			$receivers[] = new Receiver(
				(int)$currentResponsibleId,
				ProcessEntityResponsible::BECOME_RESPONSIBLE,
			);
		}

		if ($beforeSaveResponsibleId !== null)
		{
			$receivers[] = new Receiver(
				(int)$beforeSaveResponsibleId,
				ProcessEntityResponsible::NO_LONGER_RESPONSIBLE,
			);
		}

		return $receivers;
	}
}
