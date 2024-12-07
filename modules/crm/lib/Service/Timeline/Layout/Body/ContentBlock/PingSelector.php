<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin;
use Bitrix\Main\Type\DateTime;

class PingSelector extends ContentBlock
{
	use Mixin\Actionable;

	protected ?array $value = null;
	protected ?array $valuesList = null;
	protected ?string $icon = null;
	protected ?DateTime $deadline = null;

	public function getRendererName(): string
	{
		return 'PingSelector';
	}

	public function getValuesList(): ?array
	{
		return $this->valuesList;
	}

	public function setValuesList(?array $valuesList): self
	{
		$this->valuesList = $valuesList;

		return $this;
	}

	public function getValue(): ?array
	{
		return $this->value;
	}

	public function setValue(?array $value): self
	{
		$this->value = $value;

		return $this;
	}

	public function setIcon(?string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}
	public function setDeadline(?DateTime $deadline): self
	{
		$this->deadline = $deadline;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'valuesList' => $this->getValuesList(),
			'value' => $this->getValue(),
			'saveAction' => $this->getAction(),
			'icon' => $this->icon,
			'deadline' => $this->deadline,
		];
	}
}
