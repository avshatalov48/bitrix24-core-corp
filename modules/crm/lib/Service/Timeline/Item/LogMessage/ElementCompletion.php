<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Timeline\TimelineMarkType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

final class ElementCompletion extends LogMessage
{
	public function getType(): string
	{
		return 'ElementCompletion';
	}

	public function getTitle(): ?string
	{
		$code = 'CRM_TIMELINE_LOG_ELEMENT_COMPLETION_DEFAULT_TITLE';
		$associatedEntityTypeId = $this->getModel()->getAssociatedEntityTypeId();
		switch ($associatedEntityTypeId)
		{
			case CCrmOwnerType::Lead:
				$code = 'CRM_TIMELINE_LOG_ELEMENT_COMPLETION_LEAD_TITLE';
				break;
			case CCrmOwnerType::Deal:
				$code = 'CRM_TIMELINE_LOG_ELEMENT_COMPLETION_DEAL_TITLE';
				break;
			case CCrmOwnerType::Quote:
				$code = 'CRM_TIMELINE_LOG_ELEMENT_COMPLETION_QUOTE_TITLE';
				break;
			case CCrmOwnerType::Invoice:
			case CCrmOwnerType::SmartInvoice:
				$code = 'CRM_TIMELINE_LOG_ELEMENT_COMPLETION_INVOICE_TITLE';
				break;
		}

		if (CCrmOwnerType::isPossibleDynamicTypeId($associatedEntityTypeId))
		{
			$code = 'CRM_TIMELINE_LOG_ELEMENT_COMPLETION_DYNAMIC_TITLE';
		}

		return Loc::getMessage($code);
	}

	public function getIconCode(): ?string
	{
		return $this->isSuccessCompletion()
			? Icon::ARROW_UP
			: Icon::ARROW_DOWN
		;
	}

	public function getTags(): ?array
	{
		$isSuccessCompletion = $this->isSuccessCompletion();
		$stageName = $this->getStageName();
		if (!$stageName)
		{
			// default name
			$stageName = $isSuccessCompletion
				? Loc::getMessage('CRM_TIMELINE_LOG_ELEMENT_DEFAULT_TAG_SUCCESS')
				: Loc::getMessage('CRM_TIMELINE_LOG_ELEMENT_DEFAULT_TAG_FAILURE')
			;
		}

		return [
			'status' => new Tag(
				$stageName,
				$isSuccessCompletion ? Tag::TYPE_SUCCESS : Tag::TYPE_FAILURE
			)
		];
	}

	public function getContentBlocks(): ?array
	{
		$entityTypeId = $this->getContext()->getEntityTypeId();
		$associatedEntityTypeId = $this->getModel()->getAssociatedEntityTypeId();
		if (
			!CCrmOwnerType::IsDefined($entityTypeId)
			|| !CCrmOwnerType::IsDefined($associatedEntityTypeId)
		)
		{
			return null;
		}

		$entityTitle = $this->getAssociatedEntityModel()?->get('TITLE') ?? Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND');

		if ($entityTypeId === $associatedEntityTypeId)
		{
			return [
				'content' =>
					(new Text())
						->setValue($entityTitle)
						->setColor(Text::COLOR_BASE_90)
				,
			];
		}

		$entityDescription = ContentBlockFactory::createTitle(CCrmOwnerType::GetDescription($associatedEntityTypeId));
		$entityUrl = $this->getAssociatedEntityModel()?->get('SHOW_URL');

		return [
			'content' => (new LineOfTextBlocks())
				->addContentBlock('title', $entityDescription)
				->addContentBlock(
					'value',
					ContentBlockFactory::createTextOrLink(
						$entityTitle,
						$entityUrl ? new Redirect(new Uri($entityUrl)) : null
					)
				)
		];
	}

	private function getStageName(): ?string
	{
		$settings = $this->getModel()->getSettings();
		if (!$settings)
		{
			return null;
		}

		$stageId = $settings['FINAL_STAGE_ID'] ?? null;
		if (!$stageId)
		{
			return null;
		}

		$factory = Container::getInstance()
			->getFactory($this->getModel()->getAssociatedEntityTypeId())
		;

		return $factory?->getStage($stageId)?->getName();
	}

	private function isSuccessCompletion(): bool
	{
		$typeCategoryId = $this->getModel()->getTypeCategoryId();

		return $typeCategoryId === TimelineMarkType::SUCCESS;
	}
}
