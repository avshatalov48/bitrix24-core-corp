<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class Review extends Item
{
	private string $id;
	private string $datetime;
	private string|null $text = null;
	private float|null $rating = null;
	private ReviewAuthor|null $author = null;

	public function __construct(string $id, string $datetime)
	{
		$this->id = $id;
		$this->datetime = $datetime;
	}

	public function setText(string|null $text): self
	{
		$this->text = $text;

		return $this;
	}

	public function setRating(float|null $rating): self
	{
		$this->rating = $rating;

		return $this;
	}

	public function setAuthor(ReviewAuthor|null $author): self
	{
		$this->author = $author;

		return $this;
	}

	public function __toArray(): array
	{
		return [
			'id' => $this->id,
			'datetime' => $this->datetime,
			'text' => $this->text,
			'rating' => $this->rating,
			'author' => $this?->author->toArray(),
		];
	}
}
