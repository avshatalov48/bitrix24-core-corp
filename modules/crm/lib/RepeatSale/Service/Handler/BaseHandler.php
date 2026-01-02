<?php

namespace Bitrix\Crm\RepeatSale\Service\Handler;

use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Main\ArgumentOutOfRangeException;

abstract class BaseHandler
{
	protected int $entityTypeId = 0;
	protected ?int $lastEntityId = null;
	protected ?int $lastAssignmentId = null;
	protected bool $isOnlyCalc = false;
	protected int $limit = 50;
	protected int $offset = 0;

	abstract public function execute(): Result;

	public static function getType(): HandlerType
	{
		return HandlerType::SystemHandler;
	}

	public function setEntityTypeId(int $entityTypeId = \CCrmOwnerType::Contact): self
	{
		if (!Common::isClientEntityTypeId($entityTypeId))
		{
			throw new ArgumentOutOfRangeException(
				'entityTypeId',
				\CCrmOwnerType::Contact,
				\CCrmOwnerType::Company,
			);
		}

		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function setLastEntityId(?int $lastEntityId = null): self
	{
		$this->lastEntityId = $lastEntityId;

		return $this;
	}

	public function setLastAssignmentId(?int $lastAssignmentId): self
	{
		$this->lastAssignmentId = $lastAssignmentId;

		return $this;
	}

	public function setLimit(int $limit): self
	{
		$this->limit = $limit;

		return $this;
	}

	public function setOffset(int $offset): self
	{
		$this->offset = $offset;

		return $this;
	}

	public function setIsOnlyCalc(bool $value): self
	{
		$this->isOnlyCalc = $value;

		return $this;
	}
}
