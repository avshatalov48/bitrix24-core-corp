<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class ReviewAuthor extends Item
{
	private string $name;
	private string|null $image = null;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function setImage(string|null $image): self
	{
		$this->image = $image;

		return $this;
	}

	protected function __toArray(): array
	{
		return [
			'name' => $this->name,
			'image' => $this->image,
		];
	}
}
