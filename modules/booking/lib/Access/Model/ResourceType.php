<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access\Model;

use Bitrix\Booking\Access\AccessibleResourceInterface;
use Bitrix\Booking\Entity;

class ResourceType implements AccessibleResourceInterface
{
	private static array $cache = [];

	private int $id = 0;

	public static function createFromId(int $itemId): self
	{
		if ($itemId <= 0)
		{
			return self::createNew();
		}

		if (!isset(self::$cache[$itemId]))
		{
			$model = new self();
			$model->setId($itemId);
			self::$cache[$itemId] = $model;
		}

		return self::$cache[$itemId];
	}

	public static function createFromDomainObject(Entity\ResourceType\ResourceType|null $resourceType): self
	{
		if ($resourceType)
		{
			return self::createFromId($resourceType->getId());
		}

		return self::createNew();
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public static function createNew(): self
	{
		return new self();
	}
}
