<?php

namespace Bitrix\Tasks\Member\Type;

class Member
{
	private string $name = '';
	private string $workPosition = '';

	public function __construct(
		private int $userId,
		private string $role,
		private int $entityId,
		private string $entityType,
		string $name = ''
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

	public function getWorkPosition(): string
	{
		return $this->workPosition;
	}

	public function setName(?string $name): static
	{
		$this->name = (string)$name;
		return $this;
	}

	public function setWorkPosition(?string $workPosition): static
	{
		$this->workPosition = (string)$workPosition;
		return $this;
	}

	public function getEntityType(): string
	{
		return $this->entityType;
	}
}