<?php

namespace Bitrix\Crm\Integration\DocumentGenerator;

class Template implements \JsonSerializable
{
	protected $id;
	protected $title;
	protected $documentCreationUrl;
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

	public function setDocumentCreationUrl(string $documentCreationUrl): self
	{
		$this->documentCreationUrl = $documentCreationUrl;

		return $this;
	}

	public function getDocumentCreationUrl(): string
	{
		return $this->documentCreationUrl;
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
			'documentCreationUrl' => $this->documentCreationUrl,
			'isWithStamps' => $this->isWithStamps,
		];
	}
}