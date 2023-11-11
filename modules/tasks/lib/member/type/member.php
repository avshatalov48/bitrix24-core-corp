<?php

namespace Bitrix\Tasks\Member\Type;

class Member
{
	private ?string $name;

	public function __construct(
		private int $userId,
		private string $role,
		private int $entityId,
		private string $entityType,
		?string $name = null
	)
	{
		$this->name = $name;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getRole(): string
	{
		return $this->role;
	}

	public function setName(?string $name): void
	{
		$this->name = $name;
	}

	public function getEntityType(): string
	{
		return $this->entityType;
	}
}