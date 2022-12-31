<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Header;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Base;

class InfoHelper extends Base
{
	public const ICON_AUTO_SUM = 'auto-sum';

	protected ?string $iconCode = null;

	protected ?Action $primaryAction = null;

	/**
	 * @var Base[]
	 */
	protected array $textBlocks = [];

	public function getIconCode(): ?string
	{
		return $this->iconCode;
	}

	public function setIconCode(?string $iconCode): self
	{
		$this->iconCode = $iconCode;

		return $this;
	}

	public function getPrimaryAction(): ?Action
	{
		return $this->primaryAction;
	}

	public function setPrimaryAction(?Action $action): self
	{
		$this->primaryAction = $action;

		return $this;
	}

	public function addText(InfoHelperText $textBlock): self
	{
		$this->textBlocks[] = $textBlock;

		return $this;
	}

	public function addLink(InfoHelperLink $linkBlock): self
	{
		$this->textBlocks[] = $linkBlock;

		return $this;
	}

	/**
	 * @return Base[]
	 */
	public function getTextBlocks(): array
	{
		return $this->textBlocks;
	}

	public function toArray(): array
	{
		return [
			'icon' => $this->getIconCode(),
			'textBlocks' => $this->getTextBlocks(),
			'primaryAction' => $this->getPrimaryAction(),
		];
	}
}
