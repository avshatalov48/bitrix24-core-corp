<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Copilot;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin;

final class CallScoringPill extends ContentBlock
{
	use Mixin\Actionable;

	public const STATE_LOADING = 'loading';
	public const STATE_PROCESSED = 'processed';
	public const STATE_UNPROCESSED = 'unprocessed';

	protected ?string $title = null;
	protected ?string $value = null;
	protected ?string $state = null;

	public function getRendererName(): string
	{
		return 'CallScoringPill';
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $value): self
	{
		$this->title = $value;

		return $this;
	}

	public function getValue(): ?string
	{
		return $this->value;
	}

	public function setValue(?string $value): self
	{
		$this->value = $value;

		return $this;
	}

	public function getState(): ?string
	{
		return $this->state;
	}

	public function setState(?string $state): self
	{
		$this->state = $state;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'title' => $this->getTitle(),
			'value' => $this->getValue(),
			'state' => $this->getState(),
			'action' => $this->getAction(),
		];
	}
}
