<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class Resource extends Item
{
	private string $id;
	private string $title;
	private string|null $description = null;
	private string|null $information = null;
	private float|null $rating = null;
	private string|null $image = null;
	private int|null $reviewsCount = null;

	public function __construct(string $id, string $title)
	{
		$this->id = $id;
		$this->title = $title;
	}

	public function setDescription(string|null $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function setInformation(string|null $information): self
	{
		$this->information = $information;

		return $this;
	}

	public function setRating(float|null $rating): self
	{
		$this->rating = $rating;

		return $this;
	}

	public function setImage(string|null $image): self
	{
		$this->image = $image;

		return $this;
	}

	public function setReviewsCount(int|null $reviewsCount): self
	{
		$this->reviewsCount = $reviewsCount;

		return $this;
	}

	protected function __toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'description' => $this->description,
			'information' => $this->information,
			'rating' => $this->rating,
			'image' => $this->image,
			'reviewsCount' => $this->reviewsCount,
		];
	}
}
