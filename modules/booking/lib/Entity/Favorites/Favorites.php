<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Favorites;

use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Main\Type\Contract\Arrayable;

class Favorites implements Arrayable
{
	private int $managerId;
	private ResourceCollection $resources;

	public function getManagerId(): int
	{
		return $this->managerId;
	}

	public function setManagerId(int $managerId): self
	{
		$this->managerId = $managerId;

		return $this;
	}

	public function getResources(): ResourceCollection
	{
		return $this->resources;
	}

	public function setResources(ResourceCollection $resources): self
	{
		$this->resources = $resources;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'managerId' => $this->managerId,
			'resources' => $this->getResources()->toArray(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		return (new Favorites())
			->setResources($props['resources'])
			->setManagerId($props['managerId'])
		;
	}
}
