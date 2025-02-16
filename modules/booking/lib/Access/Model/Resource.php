<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access\Model;

use Bitrix\Booking\Access\AccessibleResourceInterface;
use Bitrix\Booking\Internals\Container;

class Resource implements AccessibleResourceInterface
{
	private static array $cache = [];

	private int $id = 0;
	private int $ownerId = 0;

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

	public static function createFromDomainObject(\Bitrix\Booking\Entity\Resource\Resource|null $resource): self
	{
		return $resource
			? self::createFromId($resource->getId())
				->setOwnerId($resource->getCreatedBy())
			: self::createNew()
		;
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

	public function setOwnerId(int $ownerId): self
	{
		$this->ownerId = $ownerId;

		return $this;
	}

	public function getOwnerId(): int
	{
		$this->ownerId ??= $this->ownerId = (int)$this->getResource()?->getCreatedBy();

		return $this->ownerId;
	}

	public static function createNew(): self
	{
		return new self();
	}

	private function getResource(): \Bitrix\Booking\Entity\Resource\Resource|null
	{
		return Container::getResourceRepository()->getById(
			id: $this->id,
		);
	}
}
