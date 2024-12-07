<?php

namespace Bitrix\AI\Prompt;

use Bitrix\AI\Dto\PromptType;
use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Facade\User;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Item
{
	use TranslateTrait;
	private Collection $children;

	public function __construct(
		private readonly int $id,
		private readonly ?string $section,
		private readonly ?string $sort,
		private readonly string $code,
		private readonly ?string $type,
		private readonly ?string $appCode,
		private readonly ?string $icon,
		private readonly ?string $prompt,
		private readonly string $title,
		private readonly mixed $textTranslate,
		private readonly ?array $settings,
		private readonly ?array $cacheCategory,
		private readonly bool $hasSystemCategory,
		private readonly bool $workWithResult,
		private readonly bool $isSystem,
		private readonly bool $isFavorite,
	)
	{
		$this->children = new Collection;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getSectionCode(): ?string
	{
		return $this->section;
	}

	public function getSectionTitle(): ?string
	{
		return Section::get($this->section)?->getTitle();
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getType(): ?string
	{
		return $this->type ? PromptType::fromName($this->type) : null;
	}

	public function getAppCode(): ?string
	{
		return $this->appCode;
	}

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function getPrompt(): ?string
	{
		return $this->prompt;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getText(): string
	{
		return self::translate($this->textTranslate, User::getUserLanguage());
	}

	public function hasSystemCategory(): bool
	{
		return $this->hasSystemCategory;
	}

	public function getCacheCategory(): array
	{
		return is_array($this->cacheCategory) ? $this->cacheCategory : [];
	}

	public function getSettings(): array
	{
		return is_array($this->settings) ? $this->settings : [];
	}

	public function isWorkWithResult(): bool
	{
		return $this->workWithResult;
	}

	public function isRequiredUserMessage(): bool
	{
		if ($this->prompt)
		{
			return str_contains($this->prompt, '{user_message}');
		}

		return false;
	}

	public function isRequiredOriginalMessage(): bool
	{
		if (!$this->isSystem && $this->type === PromptType::DEFAULT->name)
		{
			return true;
		}

		if ($this->prompt)
		{
			return str_contains($this->prompt, '{original_message}');
		}

		return false;
	}

	public function addChild(Item $item): void
	{
		$this->children->push($item);
	}

	public function getChildren(): Collection
	{
		return $this->children;
	}

	public function isSystem(): bool
	{
		return $this->isSystem;
	}

	public function isFavorite(): bool
	{
		return $this->isFavorite;
	}

	public function getSort(): int
	{
		return (int)$this->sort;
	}
}
