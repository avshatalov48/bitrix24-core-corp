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
	 * @param Text|Link|Date $textContentBlock
	 * @return $this
	 * @throws ArgumentTypeException
	 */
	public function addContentBlock(string $id, ContentBlock $textContentBlock): self
	{
		if (
			!($textContentBlock instanceof Text)
			&& !($textContentBlock instanceof Link)
			&& !($textContentBlock instanceof Date)
		)
		{
			throw new ArgumentTypeException(
				'textContentBlock',
				Text::class . '|' . Link::class . '|' . Date::class
			);
		}
		if (is_null($textContentBlock->getSort()))
		{
			$textContentBlock->setSort(count($this->blocks) + 1);
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
		$currentSort = 0;
		foreach ($blocks as $id => $block)
		{
			if (is_null($block->getSort()))
			{
				$block->setSort($currentSort++);
			}
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
