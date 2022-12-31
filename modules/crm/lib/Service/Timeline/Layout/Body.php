<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Note;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;

class Body extends Base
{
	protected array $blocks = [];
	protected ?Logo $logo = null;

	/**
	 * @return ContentBlock[]
	 */
	public function getContentBlocks(): array
	{
		return $this->blocks;
	}

	/**
	 * @param ContentBlock[] $blocks
	 */
	public function setContentBlocks(array $blocks): self
	{
		$this->blocks = [];
		foreach ($blocks as $id => $block)
		{
			$this->addContentBlock((string)$id, $block);
		}

		return $this;
	}

	public function addContentBlock(string $id, ContentBlock $block): self
	{
		$this->blocks[$id] = $block;

		return $this;
	}

	public function getLogo(): ?Logo
	{
		return $this->logo;
	}

	public function setLogo(?Logo $logo): self
	{
		$this->logo = $logo;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'logo' => $this->getLogo(),
			'blocks' => $this->getContentBlocks(),
		];
	}
}