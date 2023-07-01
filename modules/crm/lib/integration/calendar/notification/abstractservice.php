<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Calendar\Sharing\Link\CrmDealLink;
use Bitrix\Calendar\Sharing\Link\EventLink;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Calendar\Core\Event\Event;
use Bitrix\Crm\MessageSender\Channel;

abstract class AbstractService
{
	/** @var CrmDealLink $crmDealLink */
	protected CrmDealLink $crmDealLink;
	/** @var Event $event */
	protected Event $event;
	/** @var EventLink $eventLink */
	protected EventLink $eventLink;

	/**
	 * @param CrmDealLink $crmDealLink
	 * @return $this
	 */
	public function setCrmDealLink(CrmDealLink $crmDealLink): self
	{
		$this->crmDealLink = $crmDealLink;

		return $this;
	}

	/**
	 * @param EventLink $eventLink
	 * @return $this
	 */
	public function setEventLink(Eventlink $eventLink): self
	{
		$this->eventLink = $eventLink;

		return $this;
	}

	/**
	 * @param Event $event
	 * @return $this
	 */
	public function setEvent(Event $event): self
	{
		$this->event = $event;

		return $this;
	}

	/**
	 * @param Channel $channel
	 * @param int $contactId
	 * @param int $contactTypeId
	 * @return Channel\Correspondents\To|null
	 */
	protected function getToEntity(Channel $channel, int $contactId, int $contactTypeId): ?Channel\Correspondents\To
	{
		return current(array_filter($channel->getToList(), static function ($to) use ($contactId, $contactTypeId) {
			return $to->getAddressSource()->getEntityId() === $contactId && $to->getAddressSource()->getEntityTypeId() === $contactTypeId;
		}));
	}

	/**
	 * @param Channel $channel
	 * @param string $senderId
	 * @return Channel\Correspondents\From|null
	 */
	protected function getFromEntity(Channel $channel, string $senderId): ?Channel\Correspondents\From
	{
		return current(array_filter($channel->getFromList(), static function ($from) use ($senderId) {
			return $from->getId() === $senderId;
		}));
	}

	/**
	 * @param ItemIdentifier $entity
	 * @return bool
	 */
	abstract public static function canSendMessage(ItemIdentifier $entity): bool;

	/**
	 * @return bool
	 */
	abstract public function sendCrmSharingInvited(): bool;

	/**
	 * @return bool
	 */
	abstract public function sendCrmSharingAutoAccepted(): bool;

	/**
	 * @return bool
	 */
	abstract public function sendCrmSharingCancelled(): bool;

	/**
	 * @param ItemIdentifier $entity
	 * @return Channel|null
	 */
	abstract protected function getEntityChannel(ItemIdentifier $entity): ?Channel;
}