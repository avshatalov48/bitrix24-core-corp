<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\ArgumentTypeException;

class LineOfTextBlocks extends ContentBlock
{
	protected array $blocks = [];

	public function getRendererName(): string
	{
		return 'LineOfTextBlocks';
	}

	/**
	 * @return ContentBlock[]
	 */
	public function getContentBlocks(): array
	{
		return $this->blocks;
	}

	/**
	 * @param string $id
	 * @param Text|EditableDate $textContentBlock
	 * @return $this
	 * @throws ArgumentTypeException
	 */
	public function addContentBlock(string $id, ContentBlock $textContentBlock): self
	{
		if (!($textContentBlock instanceof Text) && !($textContentBlock instanceof EditableDate))
		{
			throw new ArgumentTypeException('textContentBlock', Text::class . '|' . EditableDate::class);
		}

		$this->blocks[$id] = $textContentBlock;

		return $this;
	}

	/**
	 * @param Text[]|EditableDate[] $blocks
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

	protected function getProperties(): array
	{
		return [
			'blocks' => $this->getContentBlocks(),
		];
	}
}
