<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

class Creation extends LogMessage
{
	public function getType(): string
	{
		return $this->getModel()->getAssociatedEntityTypeId() === \CCrmOwnerType::Activity
			? 'Activity:Creation'
			: 'Creation'
		;
	}

	public function getTitle(): ?string
	{
		$title = null;

		$assocEntityTypeId = $this->getModel()->getAssociatedEntityTypeId();
		if ($assocEntityTypeId === \CCrmOwnerType::Activity)
		{
			$activityTypeId = (int)($this->getAssociatedEntityModel()->get('TYPE_ID') ?? 0);
			$title = Loc::getMessage(
				$activityTypeId === \CCrmActivityType::Task
					? 'CRM_TIMELINE_TASK_CREATION'
					: 'CRM_TIMELINE_ACTIVITY_CREATION',
				[
					'#TITLE#' => '',
				]
			);
			$title = rtrim($title, ': ');
		}
		else
		{
			$entityTypeToTitleRelations = [
				\CCrmOwnerType::Lead => 'CRM_TIMELINE_LEAD_CREATION',
				\CCrmOwnerType::Deal => 'CRM_TIMELINE_DEAL_CREATION',
				\CCrmOwnerType::Contact => 'CRM_TIMELINE_CONTACT_CREATION',
				\CCrmOwnerType::Company => 'CRM_TIMELINE_COMPANY_CREATION',
				\CCrmOwnerType::Quote => 'CRM_TIMELINE_QUOTE_CREATION_MSGVER_1',
				\CCrmOwnerType::Invoice => 'CRM_TIMELINE_INVOICE_CREATION',
				\CCrmOwnerType::DealRecurring => 'CRM_TIMELINE_RECURRING_DEAL_CREATION',
			];
			if (isset($entityTypeToTitleRelations[$assocEntityTypeId]))
			{
				$title = Loc::getMessage($entityTypeToTitleRelations[$assocEntityTypeId]);
			}
		}
		if (!$title)
		{
			$title = $this->getHistoryItemModel()->get('TITLE');
		}

		return $title;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$assocEntityTypeId = $this->getModel()->getAssociatedEntityTypeId();
		if ($assocEntityTypeId === \CCrmOwnerType::Activity)
		{
			$subject = $this->getAssociatedEntityModel()->get('SUBJECT');
			if ($subject)
			{
				$result['subject'] = (new Link())
					->setValue($subject)
					->setAction(
						(new Action\JsEvent('Activity:View'))
							->addActionParamInt('activityId', $this->getModel()->getAssociatedEntityId())
					)
				;
			}

			$description = $this->getAssociatedEntityModel()->get('DESCRIPTION_RAW');
			if ($description)
			{
				$result['description'] = (new Text())->setValue(TruncateText($description, 128));
			}

			return $result;
		}

		$descriptionBlock = $this->getDescriptionBlock();
		if ($descriptionBlock)
		{
			$result['description'] = $descriptionBlock;
		}

		$legend = $this->getHistoryItemModel()->get('LEGEND');
		if ($legend)
		{
			$result['legend'] = (new Text())
				->setValue($legend);
		}

		$baseItem = $this->getHistoryItemModel()->get('BASE');
		if (is_array($baseItem))
		{
			$url = isset($baseItem['ENTITY_INFO']['SHOW_URL'])
				? new Uri($baseItem['ENTITY_INFO']['SHOW_URL'])
				: null;

			$result['baseItem'] = (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					ContentBlockFactory::createTitle((string)$baseItem['CAPTION'])
				)
				->addContentBlock(
					'data',
					ContentBlockFactory::createTextOrLink(
						(string)$baseItem['ENTITY_INFO']['TITLE'],
						$url ? new Redirect($url) : null
					)->setIsBold(true)
				)
			;
		}

		return $result;
	}

	private function getDescriptionBlock(): ?ContentBlock
	{
		$htmlDescription = $this->getAssociatedEntityModel()->get('HTML_TITLE');
		$textDescription = $this->getAssociatedEntityModel()->get('TITLE');
		$descriptionUrl = $this->getAssociatedEntityModel()->get('SHOW_URL');
		if ($this->isItemAboutCurrentEntity())
		{
			$descriptionUrl = null;
		}
		if ($descriptionUrl && ($textDescription || $htmlDescription))
		{
			return (new Link())
				->setValue($htmlDescription ? strip_tags($htmlDescription) : $textDescription)
				->setAction(new Redirect(new Uri($descriptionUrl)))
			;
		}

		if ($htmlDescription)
		{
			return ContentBlockFactory::createFromHtmlString($htmlDescription, 'description_');
		}

		if ($textDescription)
		{
			return (new Text())->setValue($textDescription);
		}

		return null;
	}
}
