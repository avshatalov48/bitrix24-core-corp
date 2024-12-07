<?php

namespace Bitrix\AI\ThirdParty;

use Bitrix\Main\Type\DateTime;

class Item
{
	public function __construct(
		private int $id,
		private string $name,
		private string $code,
		private string|null $appCode,
		private string $category,
		private string $completionsUrl,
		private array $settings,
		private DateTime $createdDate,
	) {}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getAppCode(): ?string
	{
		return $this->appCode;
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	public function getCompletionsUrl(): string
	{
		return $this->completionsUrl;
	}

	public function getSettings(): array
	{
		return $this->settings;
	}

	public function getOption(string $key): mixed
	{
		return $this->settings[$key] ?? null;
	}

	public function getCreatedDate(): DateTime
	{
		return $this->createdDate;
	}
}
