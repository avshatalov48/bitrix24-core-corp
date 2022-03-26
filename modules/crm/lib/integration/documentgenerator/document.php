<?php

namespace Bitrix\Crm\Integration\DocumentGenerator;

class Document implements \JsonSerializable
{
	protected $id;
	protected $title;
	protected $detailUrl;
	protected $isWithStamps;

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setDetailUrl(string $detailUrl): self
	{
		$this->detailUrl = $detailUrl;

		return $this;
	}

	public function getDetailUrl(): ?string
	{
		return $this->detailUrl;
	}

	public function setIsWithStamps(bool $isWithStamps): self
	{
		$this->isWithStamps = $isWithStamps;

		return $this;
	}

	public function getIsWithStamps(): bool
	{
		return $this->isWithStamps ?? false;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'detailUrl' => $this->detailUrl,
			'isWithStamps' => $this->isWithStamps,
		];
	}
}