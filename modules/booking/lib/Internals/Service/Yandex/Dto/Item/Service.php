<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\ServiceResourceCollection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class Service extends Item
{
	private string $id;
	private string $title;
	private string|null $description = null;
	private string|null $category = null;
	private string|null $image = null;
	private PriceRange|null $price = null;
	private int|null $durationSeconds = null;
	private ServiceResourceCollection $resources;

	public function __construct(string $id, string $title)
	{
		$this->id = $id;
		$this->title = $title;

		$this->resources = new ServiceResourceCollection();
	}

	public function setDescription(string|null $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function setCategory(string|null $category): self
	{
		$this->category = $category;

		return $this;
	}

	public function setImage(string|null $image): self
	{
		$this->image = $image;

		return $this;
	}

	public function setPrice(PriceRange|null $price): self
	{
		$this->price = $price;

		return $this;
	}

	public function setDurationSeconds(int|null $durationSeconds): self
	{
		$this->durationSeconds = $durationSeconds;

		return $this;
	}

	public function addResource(ServiceResource $resource): self
	{
		$this->resources->add($resource);

		return $this;
	}

	protected function __toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'description' => $this->description,
			'category' => $this->category,
			'image' => $this->image,
			'price' => $this->price?->toArray(),
			'durationSeconds' => $this->durationSeconds,
			'resources' => $this->resources->toArray(),
		];
	}
}
