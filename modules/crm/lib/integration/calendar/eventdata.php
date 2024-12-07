<?php

namespace Bitrix\Crm\Integration\Calendar;

use Bitrix\Calendar\Sharing;
use Bitrix\Calendar\Sharing\Link\CrmDealLink;

class EventData
{
	private ?string $eventType = null;
	private ?int $entityTypeId = null;
	private ?int $entityId = null;
	private ?int $contactTypeId = null;
	private ?int $contactId = null;
	private ?int $ownerId = null;
	private ?int $timestamp = null;
	private ?string $linkHash = null;
	private ?int $associatedEntityId = null;
	private ?int $associatedEntityTypeId = null;
	private ?int $linkId = null;
	private ?string $contactCommunication = null;
	private ?string $channelName = null;
	private ?array $sharingRuleArray = null;
	private ?array $memberIds = null;

	public const SHARING_ON_NOT_VIEWED = 'SHARING_ON_NOT_VIEWED';
	public const SHARING_ON_VIEWED = 'SHARING_ON_VIEWED';
	public const SHARING_ON_EVENT_CREATED = 'SHARING_ON_EVENT_CREATED';
	public const SHARING_ON_EVENT_DOWNLOADED = 'SHARING_ON_EVENT_DOWNLOADED';
	public const SHARING_ON_INVITATION_SENT = 'SHARING_ON_INVITATION_SENT';
	public const SHARING_ON_EVENT_CONFIRMED = 'SHARING_ON_EVENT_CONFIRMED';
	public const SHARING_ON_LINK_COPIED = 'SHARING_ON_LINK_COPIED';
	public const SHARING_ON_RULE_UPDATED = 'SHARING_ON_RULE_UPDATED';

	public function getEventType()
	{
		return $this->eventType;
	}

	public function getEntityTypeId()
	{
		return $this->entityTypeId;
	}

	public function getEntityId()
	{
		return $this->entityId;
	}

	public function getContactTypeId()
	{
		return $this->contactTypeId;
	}

	public function getContactId()
	{
		return $this->contactId;
	}

	public function getOwnerId()
	{
		return $this->ownerId;
	}

	public function getTimestamp()
	{
		return $this->timestamp;
	}

	public function getLinkHash(): ?string
	{
		return $this->linkHash;
	}

	public function getLinkId(): ?int
	{
		return $this->linkId;
	}

	public function getAssociatedEntityId(): ?int
	{
		return $this->associatedEntityId;
	}

	public function getAssociatedEntityTypeId(): ?int
	{
		return $this->associatedEntityTypeId;
	}

	public function getContactCommunication(): ?string
	{
		return $this->contactCommunication;
	}

	public function getChannelName(): ?string
	{
		return $this->channelName;
	}

	public function getMemberIds(): ?array
	{
		return $this->memberIds;
	}

	public function setMemberIds(?array $memberIds): self
	{
		$this->memberIds = $memberIds;

		return $this;
	}

	public function setEventType(?string $eventType)
	{
		$this->eventType = $eventType;

		return $this;
	}

	public function setEntityTypeId(?int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function setEntityId(?int $entityId)
	{
		$this->entityId = $entityId;

		return $this;
	}

	public function setContactTypeId(?int $contactTypeId)
	{
		$this->contactTypeId = $contactTypeId;

		return $this;
	}

	public function setContactId(?int $contactId)
	{
		$this->contactId = $contactId;

		return $this;
	}

	public function setOwnerId(?int $ownerId)
	{
		$this->ownerId = $ownerId;

		return $this;
	}

	public function setTimestamp(?int $timestamp)
	{
		$this->timestamp = $timestamp;

		return $this;
	}

	public function setLinkHash(?string $linkHash): self
	{
		$this->linkHash = $linkHash;

		return $this;
	}

	public function setLinkId(?int $linkId): self
	{
		$this->linkId = $linkId;

		return $this;
	}

	public function setAssociatedEntityId(?int $associatedEntityId): self
	{
		$this->associatedEntityId = $associatedEntityId;

		return $this;
	}

	public function setAssociatedEntityTypeId(?int $associatedEntityTypeId): self
	{
		$this->associatedEntityTypeId = $associatedEntityTypeId;

		return $this;
	}

	public function setContactCommunication(?string $contactCommunication): self
	{
		$this->contactCommunication = $contactCommunication;

		return $this;
	}

	public function setChannelName(?string $channelName): self
	{
		$this->channelName = $channelName;

		return $this;
	}

	public function getSharingRuleArray(): ?array
	{
		return $this->sharingRuleArray;
	}

	public function setSharingRuleArray(?array $sharingRule): self
	{
		$this->sharingRuleArray = $sharingRule;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'eventType' => $this->eventType,
			'entityTypeId' => $this->entityTypeId,
			'entityId' => $this->entityId,
			'contactTypeId' => $this->contactTypeId,
			'contactId' => $this->contactId,
			'ownerId' => $this->ownerId,
			'timestamp' => $this->timestamp,
			'linkUrl' => $this->linkHash,
			'associatedEntityId' => $this->associatedEntityId,
			'associatedEntityTypeId' => $this->associatedEntityTypeId,
		];
	}

	public static function createFromCrmDealLink(CrmDealLink $crmDealLink, string $eventType): self
	{
		$eventData = new self();
		$sharingRuleArray = (new Sharing\Link\Rule\Mapper())->convertToArray($crmDealLink->getSharingRule());
		$eventData
			->setEventType($eventType)
			->setOwnerId($crmDealLink->getOwnerId())
			->setEntityTypeId(\CCrmOwnerType::Deal)
			->setEntityId($crmDealLink->getEntityId())
			->setContactTypeId($crmDealLink->getContactType())
			->setContactId($crmDealLink->getContactId())
			->setTimestamp(time())
			->setLinkHash($crmDealLink->getHash())
			->setLinkId($crmDealLink->getId())
			->setSharingRuleArray($sharingRuleArray)
			->setMemberIds(array_map(static fn($member) => $member->getId(), $crmDealLink->getMembers()))
		;

		return $eventData;
	}
}