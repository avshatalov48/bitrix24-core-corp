<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\TextPropertiesInterface;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait HasCheckDetails
{
	public function getCheckDetailsContentBlock(): LineOfTextBlocks
	{
		$result = new LineOfTextBlocks();

		if (!$this->isCheckAssociatedEntityModelSupported())
		{
			return $result;
		}

		$entityNameBlock = (new Text())->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_NAME'));
		$action = $this->getOpenCheckAction();
		$titleBlocks = $this->getCheckTitleContentBlock();

		if ($action)
		{
			$entityNameBlock->setFontSize(Text::FONT_SIZE_SM);
			$entityNameBlock->setColor(Text::COLOR_BASE_70);
		}
		else
		{
			$entityNameBlock->setColor(Text::COLOR_BASE_90);
		}

		$result->addContentBlock('entityName', $entityNameBlock);
		foreach ($titleBlocks->getContentBlocks() as $index => $titleContentBlock)
		{
			$result->addContentBlock('title' . $index, $titleContentBlock);
		}

		return $result;
	}

	public function getCheckTitleContentBlock(): LineOfTextBlocks
	{
		$result = new LineOfTextBlocks();

		if (!$this->isCheckAssociatedEntityModelSupported())
		{
			return $result;
		}

		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_TITLE',
			[
				'#NUMBER#' => $this->getAssociatedEntityModel()->get('ID'),
				'#DATE#' => $this->getAssociatedEntityModel()->get('DATE_CREATE_FORMATTED'),
			]
		);

		$action = $this->getOpenCheckAction();

		if ($action)
		{
			$titleBlock = (new Link())
				->setValue($title)
				->setAction($action)
			;
		}
		else
		{
			$titleBlock = (new Text())
				->setValue($title)
				->setColor(Text::COLOR_BASE_90)
			;
		}
		$result->addContentBlock('title', $titleBlock);

		$sum = $this->getAssociatedEntityModel()->get('SUM');
		$currency = $this->getAssociatedEntityModel()->get('CURRENCY');
		if ($sum && $currency)
		{
			$amountBlocks = ContentBlockFactory::getBlocksFromTemplate(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_FOR_AMOUNT'),
				[
					'#AMOUNT#' =>
						(new Money())
							->setOpportunity((float)$sum)
							->setCurrencyId((string)$currency)
					,
				]
			);

			foreach ($amountBlocks as $index => $amountBlock)
			{
				if (!$amountBlock instanceof TextPropertiesInterface)
				{
					continue;
				}

				$result->addContentBlock(
					'amountBlock' . $index,
					$amountBlock->setColor(Text::COLOR_BASE_90)
				);
			}
		}

		return $result;
	}

	public function getOpenCheckAction(): ?Action
	{
		if (!$this->isCheckAssociatedEntityModelSupported())
		{
			return null;
		}

		$url = $this->getAssociatedEntityModel()->get('SHOW_URL');
		if (!$url)
		{
			return null;
		}

		$shortTitle = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_TITLE',
			[
				'#NUMBER#' => $this->getAssociatedEntityModel()->get('ID'),
				'#DATE#' => ConvertTimeStamp($this->getAssociatedEntityModel()->get('DATE_CREATE')->getTimestamp()),
			]
		);

		$action = new Action\JsEvent('OrderCheck:OpenCheck');

		return $action
			->addActionParamString('checkUrl', $url)
			->addActionParamString('shortTitle', $shortTitle)
			->addActionParamString('entityName', Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_NAME'))
		;
	}

	public function getCheckInFiscalDataOperatorAction(): ?Action
	{
		if (!$this->isCheckAssociatedEntityModelSupported())
		{
			return null;
		}

		$url = $this->getAssociatedEntityModel()->get('CHECK_URL');
		if (!$url)
		{
			return null;
		}
		$action = new Action\Redirect(new Uri($url));

		return $action->addActionParamString('target', '_blank');
	}

	private function isCheckAssociatedEntityModelSupported(): bool
	{
		return $this->getModel()->getAssociatedEntityTypeId() === \CCrmOwnerType::OrderCheck;
	}

	abstract public function getModel(): Model;

	abstract protected function getAssociatedEntityModel(): ?AssociatedEntityModel;
}
