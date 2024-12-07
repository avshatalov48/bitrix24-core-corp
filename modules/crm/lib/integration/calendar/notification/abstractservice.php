<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Calendar\Sharing\Link\CrmDealLink;
use Bitrix\Calendar\Sharing\Link\EventLink;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Calendar\Core\Event\Event;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\Correspondents\From;
use Bitrix\Crm\MessageSender\Channel\Correspondents\To;

abstract class AbstractService
{
	/** @var ?CrmDealLink $crmDealLink */
	protected ?CrmDealLink $crmDealLink;
	/** @var Event $event */
	protected Event $event;
	/** @var EventLink $eventLink */
	protected EventLink $eventLink;
	/** @var ?Event $oldEvent */
	protected ?Event $oldEvent = null;

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
	 * @param Event $oldEvent
	 * @return $this
	 */
	public function setOldEvent(Event $oldEvent): self
	{
		$this->oldEvent = $oldEvent;

		return $this;
	}

	/**
	 * @param Channel $channel
	 * @param int $contactId
	 * @param int $contactTypeId
	 * @return To|bool
	 */
	protected function getToEntity(Channel $channel, int $contactId, int $contactTypeId): Channel\Correspondents\To|bool
	{
		return current(array_filter($channel->getToList(), static function ($to) use ($contactId, $contactTypeId) {
			return $to->getAddressSource()->getEntityId() === $contactId && $to->getAddressSource()->getEntityTypeId() === $contactTypeId;
		}));
	}

	/**
	 * @param Channel $channel
	 * @param string $senderId
	 * @return From|bool
	 */
	protected function getFromEntity(Channel $channel, string $senderId): Channel\Correspondents\From|bool
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
	 * @return bool
	 */
	abstract public function sendCrmSharingEdited(): bool;

	/**
	 * @param ItemIdentifier $entity
	 * @return Channel|null
	 */
	abstract protected function getEntityChannel(ItemIdentifier $entity): ?Channel;
}