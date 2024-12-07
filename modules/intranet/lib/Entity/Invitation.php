<?php

namespace Bitrix\Intranet\Entity;

use Bitrix\Intranet\Enum\InvitationType;

class Invitation
{
	public function __construct(
		private ?int $userId,
		private ?bool $initialized = false,
		private ?bool $isMass = false,
		private ?bool $isDepartment = false,
		private ?bool $isIntegrator = false,
		private ?bool $isRegister = false,
		private ?int $id = null,
		private ?int $originatorId,
		private ?InvitationType $type,
	)
	{
	}

	public function getUserId(): ?int
	{
		return $this->userId;
	}

	public function setUserId(?int $userId): void
	{
		$this->userId = $userId;
	}

	public function isInitialized(): ?bool
	{
		return $this->initialized;
	}

	public function setInitialized(?bool $initialized): void
	{
		$this->initialized = $initialized;
	}

	public function isMass(): ?bool
	{
		return $this->isMass;
	}

	public function setIsMass(?bool $isMass): void
	{
		$this->isMass = $isMass;
	}

	public function isDepartment(): ?bool
	{
		return $this->isDepartment;
	}

	public function setIsDepartment(?bool $isDepartment): void
	{
		$this->isDepartment = $isDepartment;
	}

	public function isIntegrator(): ?bool
	{
		return $this->isIntegrator;
	}

	public function setIsIntegrator(?bool $isIntegrator): void
	{
		$this->isIntegrator = $isIntegrator;
	}

	public function isRegister(): ?bool
	{
		return $this->isRegister;
	}

	public function setIsRegister(?bool $isRegister): void
	{
		$this->isRegister = $isRegister;
	}

	public function getOriginatorId(): ?int
	{
		return $this->originatorId;
	}

	public function setOriginatorId(?int $originatorId): void
	{
		$this->originatorId = $originatorId;
	}

	public function getType(): ?InvitationType
	{
		return $this->type;
	}

	public function setType(?InvitationType $type): void
	{
		$this->type = $type;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): void
	{
		$this->id = $id;
	}
}