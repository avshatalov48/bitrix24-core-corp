<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard\Tabs;

abstract class Base implements \JsonSerializable
{
	private $id;
	private $title;
	private $selectable = true;
	private $desktopUrl;
	private $payload = [];

	public function __construct(string $id, string $title = null)
	{
		$this->id = $id;
		$this->title = $title;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getTitle(): string
	{
		return $this->title ?? $this->getDefaultTitle();
	}

	public function setSelectable(bool $selectable): self
	{
		$this->selectable = $selectable;

		return $this;
	}

	public function getSelectable(): bool
	{
		return $this->selectable;
	}

	public function setDesktopUrl(?string $url): self
	{
		$this->desktopUrl = $url;

		return $this;
	}

	public function getDesktopUrl(): ?string
	{
		return $this->desktopUrl;
	}

	abstract protected function getDefaultTitle(): string;

	public function getType(): string
	{
		return static::TYPE;
	}

	public function setPayload(array $payload): self
	{
		$this->payload = $payload;

		return $this;
	}

	public function getPayload(): array
	{
		return $this->payload;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'selectable' => $this->getSelectable(),
			'desktopUrl' => $this->getDesktopUrl(),
			'type' => $this->getType(),
			'payload' => $this->getPayload(),
		];
	}
}
