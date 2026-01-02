<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

final class ErrorBlock extends ContentBlock
{
	public const ERROR_TYPE_AI = 'ai';

	protected string $title;
	protected string $description;
	protected bool $closable = false;
	protected ?string $type = null;

	public function getRendererName(): string
	{
		return 'ErrorBlock';
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function getClosable(): bool
	{
		return $this->closable;
	}

	public function setClosable(bool $closable): self
	{
		$this->closable = $closable;

		return $this;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType(?string $type): self
	{
		$this->type = $type;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'closable' => $this->closable,
			'type' => $this->getType(),
		];
	}
}
