<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Factory\RestAppConfigurable;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ActionDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\DeadlineDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\LineOfBlocksDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\LinkDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\TextDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\WithTitleDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Type\DateTime;

class ContentBlockFactory
{
	public function __construct(
		private readonly Item\Configurable $item,
		private readonly ActionFactory $actionFactory,
	)
	{
	}

	public function createByDto(ContentBlockDto|null $contentBlockDto): ContentBlock|null
	{
		if ($contentBlockDto === null || $contentBlockDto->hasValidationErrors())
		{
			return null;
		}

		$properties = $contentBlockDto->properties ?? new \stdClass();

		switch ($contentBlockDto->type)
		{
			case ContentBlockDto::TYPE_TEXT:
				/** @var $properties TextDto */
				return (new ContentBlock\Text())
					->setValue($properties->value)
					->setIsMultiline($properties->multiline)
					->setTitle($properties->title)
					->setIsBold($properties->bold)
					->setFontSize($properties->size)
					->setColor($properties->color)
					->setScope($properties->scope)
				;

			case  ContentBlockDto::TYPE_LARGE_TEXT:
				return (new ContentBlock\EditableDescription())
					->setText($properties->value)
					->setEditable(false)
					->setHeight(ContentBlock\EditableDescription::HEIGHT_LONG)
				;

			case ContentBlockDto::TYPE_LINK:
				/** @var $properties LinkDto */
				return (new ContentBlock\Link())
					->setValue($properties->text)
					->setIsBold($properties->bold)
					->setAction($this->createAction($properties->action))
					->setScope($properties->scope)
				;

			case ContentBlockDto::TYPE_DEADLINE:
				/** @var $properties DeadlineDto */
				if ($this->getDeadline())
				{
					$readonly = !$this->isScheduled() || ($properties->readonly ?? false);

					return (new ContentBlock\EditableDate())
						->setStyle(ContentBlock\EditableDate::STYLE_PILL)
						->setDate($this->getDeadline())
						->setAction($readonly ? null : $this->getChangeDeadlineAction())
						->setBackgroundColor($readonly ? null : ContentBlock\EditableDate::BACKGROUND_COLOR_WARNING)
						->setScope($properties->scope)
					;
				}

				return null;

			case ContentBlockDto::TYPE_WITH_TITLE:
				/** @var $properties WithTitleDto */
				if (!$properties->block || !$this->isValidChildContentBlock($properties->block))
				{
					return null;
				}

				$childBlock = $this->createByDto($properties->block);
				if (!$childBlock)
				{
					return null;
				}

				return (new ContentBlock\ContentBlockWithTitle())
					->setTitle($properties->title)
					->setWordWrap(true)
					->setInline($properties->inline)
					->setContentBlock($childBlock)
					->setScope($properties->scope)
				;

			case ContentBlockDto::TYPE_LINE_OF_BLOCKS:
				/** @var $properties LineOfBlocksDto */
				if (!is_array($properties->blocks))
				{
					return null;
				}
				$blocks = [];
				foreach ($properties->blocks as $blockId => $blockDto)
				{
					if (!$this->isValidChildContentBlock($blockDto))
					{
						continue;
					}

					$block = $this->createByDto($blockDto);
					if ($block)
					{
						$blocks[(string)$blockId] = $block;
					}
				}
				if (empty($blocks))
				{
					return null;
				}

				return (new ContentBlock\LineOfTextBlocks())
					->setScope($properties->scope)
					->setContentBlocks($blocks)
				;
		}

		return null;
	}

	private function isValidChildContentBlock(ContentBlockDto|null $contentBlock): bool
	{
		if ($contentBlock === null || $contentBlock->hasValidationErrors())
		{
			return false;
		}

		$availableChildContentBlocks = [
			ContentBlockDto::TYPE_TEXT,
			ContentBlockDto::TYPE_LINK,
			ContentBlockDto::TYPE_DEADLINE,
		];

		return in_array($contentBlock->type, $availableChildContentBlocks, true);
	}

	private function createAction(ActionDto|null $actionDto): Action|null
	{
		return $this->actionFactory->createByDto($actionDto);
	}

	private function isScheduled(): bool
	{
		return $this->item->getModel()->isScheduled();
	}

	private function getDeadline(): DateTime|null
	{
		if ($this->item instanceof Item\Interfaces\Deadlinable)
		{
			return $this->item->getDeadline();
		}

		return null;
	}

	private function getChangeDeadlineAction(): Action|null
	{
		if ($this->item instanceof Item\Interfaces\Deadlinable)
		{
			return $this->item->getChangeDeadlineAction();
		}

		return null;
	}
}
