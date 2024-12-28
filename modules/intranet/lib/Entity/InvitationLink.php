<?php

namespace Bitrix\Intranet\Entity;

use Bitrix\Intranet\Enum\LinkEntityType;
use Bitrix\Main\Type\DateTime;

class InvitationLink
{
	public function __construct(
		private readonly int            $entityId,
		private readonly LinkEntityType $entityType,
		private readonly string         $code,
		private readonly ?int           $id = null,
		private readonly ?int           $createdBy= null,
		private readonly ?DateTime      $createdAt = null,
		private readonly ?DateTime      $expiredAt= null,
	)
	{}

	public function getEntityType(): LinkEntityType
	{
		return $this->entityType;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getCreatedBy(): ?int
	{
		return $this->createdBy;
	}

	public function getCreatedAt(): ?DateTime
	{
		return $this->createdAt;
	}

	public function getExpiredAt(): ?DateTime
	{
		return $this->expiredAt;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}
}