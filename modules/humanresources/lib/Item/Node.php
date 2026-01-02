<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\Type\DateTime;

class Node implements Item
{
	public function __construct(
		public ?string $name = null,
		public ?NodeEntityType $type = null,
		public ?int $structureId = null,
		public ?string $accessCode = null,
		public ?int $id = null,
		public ?int $parentId = null,
		private ?int $depth = null,
		public ?int $createdBy = null,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
		public ?string $xmlId = null,
		public ?bool $active = true,
		public ?bool $globalActive = true,
		public ?int $sort = 0,
		public ?string $description = null,
		public ?string $colorName = null,
	) {}

	public function __get(string $fieldName)
	{
		// lazy loading for node field
		if ($fieldName === 'depth' && $this->id !== null && $this->depth === null)
		{
			$this->depth = Container::getNodeRepository()->getById($this->id, true)?->depth;

			return $this->depth;
		}

		if (!isset($this->$fieldName))
		{
			throw new \InvalidArgumentException("Invalid property: $fieldName");
		}

		return $this->$fieldName;
	}

	public function __set(string $fieldName, mixed $value)
	{
		if ($fieldName === 'depth')
		{
			$this->depth = $value;
		}
	}

	public function isDepartment(): bool
	{
		return $this->type?->isDepartment() ?? false;
	}

	public function isTeam(): bool
	{
		return $this->type?->isTeam() ?? false;
	}
}