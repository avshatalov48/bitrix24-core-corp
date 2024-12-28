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
		if ($this->getModel()->getAssociatedEntityTypeId() !== \CCrmOwnerType::OrderShipment)
		{
			return new ContentBlock\LineOfTextBlocks();
		}

		$title = Loc::getMessage(
			'CRM_TIMELINE_ECOMMERCE_SHIPMENT_ENTITY_TITLE_MSGVER_1',
			[
				'#NUMBER#' => $this->getAssociatedEntityModel()->get('ACCOUNT_NUMBER'),
				'#DATE#' => $this->getAssociatedEntityModel()->get('DATE_INSERT_FORMATTED'),
			]
		);

		$pregMatchResult = null;
		preg_match('/\<a\>(.*)\<\/a\>/', $title, $pregMatchResult);
		[$linkNumberAndDate, $numberAndDate] = $pregMatchResult;
		$title = str_replace($linkNumberAndDate, '#NUMBER_AND_DATE#', $title);

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
			$numberAndDateBlock = (new ContentBlock\Link())
				->setValue($numberAndDate)
				->setAction($action)
			;
		}
		else
		{
			$entityNameBlock->setColor(ContentBlock\Text::COLOR_BASE_90);
			$numberAndDateBlock = (new ContentBlock\Text())
				->setValue($numberAndDate)
				->setColor(ContentBlock\Text::COLOR_BASE_90)
			;
		}

		$priceDelivery = $this->getAssociatedEntityModel()->get('PRICE_DELIVERY');
		$currency = $this->getAssociatedEntityModel()->get('CURRENCY');
		$amountBlock = (new ContentBlock\Money())
			->setOpportunity((float)$priceDelivery)
			->setCurrencyId((string)$currency)
		;

		return ContentBlockFactory::createLineOfTextFromTemplate(
			$title,
			[
				'#NAME#' => $entityNameBlock,
				'#NUMBER_AND_DATE#' => $numberAndDateBlock,
				'#AMOUNT#' => $amountBlock,
			],
		);
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

		$shipmentId = (int)$this->getAssociatedEntityModel()->get('ID');
		$detailLink = EntityLinkBuilder::getInstance()->getShipmentDetailsLink($shipmentId);
		if ($detailLink && !$this->isItemAboutCurrentEntity())
		{
			return new Action\Redirect(new Uri($detailLink));
		}

		return null;
	}
}
