<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Header;

use Bitrix\Crm\Service\Timeline\Layout\Base;

class User extends Base
{
	protected string $title;
	protected string $detailUrl;
	protected ?string $imageUrl;

	public function __construct(string $title, string $detailUrl, ?string $imageUrl = null)
	{
		$this->title = $title;
		$this->detailUrl = $detailUrl;
		$this->imageUrl = $imageUrl;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getDetailUrl(): string
	{
		return $this->detailUrl;
	}

	public function getImageUrl(): ?string
	{
		return $this->imageUrl;
	}

	public function toArray(): array
	{
		return [
			'title' => $this->getTitle(),
			'detailUrl' => $this->getDetailUrl(),
			'imageUrl' => $this->getImageUrl(),
		];
	}
}
