<?php

namespace Bitrix\Calendar\Sharing\Link;

class CrmDealLink extends Link
{
	/** @var int $slotSize */
	private int $slotSize = 60;
	/** @var int $ownerId */
	private int $ownerId;
	/** @var int|null $contactId */
	private ?int $contactId = null;
	/** @var int|null $contactType */
	private ?int $contactType = null;
	/** @var string|null $channelId */
	private ?string $channelId = null;
	/** @var string|null $senderId */
	private ?string $senderId = null;
	/** @var string|null $lastStatus */
	private ?string $lastStatus = null;

	public function getObjectType(): string
	{
		return Helper::CRM_DEAL_SHARING_TYPE;
	}

	public function getSlotSize(): int
	{
		return $this->slotSize;
	}

	public function getEntityId(): int
	{
		return $this->getObjectId();
	}

	public function getContactId(): ?int
	{
		return $this->contactId;
	}

	public function getContactType(): ?int
	{
		return $this->contactType;
	}

	public function getOwnerId(): int
	{
		return $this->ownerId;
	}

	public function getChannelId(): ?string
	{
		return $this->channelId;
	}

	public function getSenderId(): ?string
	{
		return $this->senderId;
	}

	public function getLastStatus(): ?string
	{
		return $this->lastStatus;
	}

	public function setSlotSize(int $minutes): self
	{
		$this->slotSize = $minutes;

		return $this;
	}

	public function setEntityId(int $id): self
	{
		return $this->setObjectId($id);
	}

	public function setContactId(?int $contactId): self
	{
		$this->contactId = $contactId;

		return $this;
	}

	public function setContactType(?int $contactType): self
	{
		$this->contactType = $contactType;

		return $this;
	}

	public function setOwnerId(int $ownerId): self
	{
		$this->ownerId = $ownerId;

		return $this;
	}

	public function setChannelId(?string $channelId): self
	{
		$this->channelId = $channelId;

		return $this;
	}

	public function setSenderId(?string $senderId): self
	{
		$this->senderId = $senderId;

		return $this;
	}

	public function setLastStatus(?string $lastStatus): self
	{
		$this->lastStatus = $lastStatus;

		return $this;
	}
}