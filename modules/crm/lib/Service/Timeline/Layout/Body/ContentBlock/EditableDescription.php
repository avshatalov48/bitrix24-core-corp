<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class EditableDescription extends ContentBlock
{
	use Actionable;

	public const HEIGHT_SHORT = 'short';
	public const HEIGHT_LONG = 'long';

	public const BG_COLOR_YELLOW = 'yellow';
	public const BG_COLOR_WHITE = 'white';

	protected ?string $text = null;
	protected ?string $backgroundColor = null;
	protected ?bool $editable = true;
	protected string $height = self::HEIGHT_LONG;

	public function getRendererName(): string
	{
		return 'EditableDescription';
	}

	public function getText(): ?string
	{
		return $this->text;
	}

	public function setText(?string $text): self
	{
		$this->text = $text;

		return $this;
	}

	public function getEditable(): bool
	{
		return $this->editable;
	}

	public function setEditable(bool $editable): self
	{
		$this->editable = $editable;

		return $this;
	}

	public function getHeight(): string
	{
		return $this->height;
	}

	public function setHeight(string $height): self
	{
		$this->height = $height;

		return $this;
	}

	public function getBackgroundColor(): ?string
	{
		return $this->backgroundColor;
	}

	public function setBackgroundColor(string $backgroundColor): self
	{
		$this->backgroundColor = $backgroundColor;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'text' => html_entity_decode($this->getText()),
			'saveAction' => $this->getAction(),
			'editable' => $this->getEditable(),
			'height' => $this->getHeight(),
			'backgroundColor' => $this->getBackgroundColor(),
		];
	}
}
