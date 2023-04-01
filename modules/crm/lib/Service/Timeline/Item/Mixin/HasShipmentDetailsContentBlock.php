<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Sale\EntityLinkBuilder\EntityLinkBuilder;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait HasShipmentDetailsContentBlock
{
	public function getShipmentDetailsContentBlock(): ContentBlock\LineOfTextBlocks
	{
		$result = new ContentBlock\LineOfTextBlocks();

		if ($this->getModel()->getAssociatedEntityTypeId() !== \CCrmOwnerType::OrderShipment)
		{
			return $result;
		}

		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_SHIPMENT_ENTITY_TITLE',
			[
				'#NUMBER#' => $this->getAssociatedEntityModel()->get('ACCOUNT_NUMBER'),
				'#DATE#' => $this->getAssociatedEntityModel()->get('DATE_INSERT_FORMATTED'),
			]
		);
		$entityNameBlock =
			(new ContentBlock\Text())
				->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SHIPMENT_ENTITY_NAME'))
		;
		$action = $this->getShipmentDetailsEntityNameAction();

		if ($action)
		{
			$entityNameBlock
				->setFontSize(ContentBlock\Text::FONT_SIZE_SM)
				->setColor(ContentBlock\Text::COLOR_BASE_70)
			;
			$titleBlock = (new ContentBlock\Link())
				->setValue($title)
				->setAction($action)
			;
		}
		else
		{
			$entityNameBlock->setColor(ContentBlock\Text::COLOR_BASE_90);
			$titleBlock = (new ContentBlock\Text())
				->setValue($title)
				->setColor(ContentBlock\Text::COLOR_BASE_90)
			;
		}

		$result
			->addContentBlock('entityName', $entityNameBlock)
			->addContentBlock('title', $titleBlock)
		;

		$priceDelivery = $this->getAssociatedEntityModel()->get('PRICE_DELIVERY');
		$currency = $this->getAssociatedEntityModel()->get('CURRENCY');
		if ($priceDelivery && $currency)
		{
			$amountBlocks = ContentBlockFactory::getBlocksFromTemplate(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_WITH_PRICE'),
				[
					'#AMOUNT#' => (new ContentBlock\Money())
						->setOpportunity((float)$priceDelivery)
						->setCurrencyId((string)$currency)
					,
				]
			);

			foreach ($amountBlocks as $index => $amountBlock)
			{
				if (!$amountBlock instanceof ContentBlock\TextPropertiesInterface)
				{
					continue;
				}

				$result->addContentBlock(
					'priceDelivery' . $index,
					$amountBlock->setColor(ContentBlock\Text::COLOR_BASE_90)
				);
			}
		}

		return $result;
	}

	abstract public function getModel(): Model;

	abstract protected function getAssociatedEntityModel(): ?AssociatedEntityModel;

	private function getShipmentDetailsEntityNameAction(): ?Action
	{
		$contextEntityTypeId = $this->getContext()->getEntityTypeId();

		/**
		 * Currently Order, Payment and Shipment pages are forced to work in \CCrmOwnerType::Order context
		 * which makes it impossible to show any links to these entities in this context
		 */
		if ($contextEntityTypeId === \CCrmOwnerType::Order)
		{
			return null;
		}

		$detailLink = EntityLinkBuilder::getInstance()->getShipmentDetailsLink(
			$this->getAssociatedEntityModel()->get('ID')
		);
		if ($detailLink && !$this->isItemAboutCurrentEntity())
		{
			return new Action\Redirect(new Uri($detailLink));
		}

		return null;
	}
}
