<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait HasDeliveryMethodContentBlock
{
	public function getDeliveryMethodContentBlock(): LineOfTextBlocks
	{
		$result = new LineOfTextBlocks();

		if ($this->getModel()->getAssociatedEntityTypeId() !== \CCrmOwnerType::OrderShipment)
		{
			return $result;
		}

		$result
			->addContentBlock(
				'deliveryMethodTitle',
				(new Text())
					->setValue(
						Loc::getMessage('CRM_TIMELINE_ECOMMERCE_SHIPMENT_DELIVERY_METHOD')
					)
					->setFontSize(Text::FONT_SIZE_SM)
					->setColor(Text::COLOR_BASE_70)
			)
			->addContentBlock(
				'deliveryMethodName',
				(new Text())
					->setValue(
						(string)$this->getAssociatedEntityModel()->get('DELIVERY_NAME')
					)
					->setColor(Text::COLOR_BASE_90)
			)
		;

		return $result;
	}

	abstract public function getModel(): Model;

	abstract protected function getAssociatedEntityModel(): ?AssociatedEntityModel;
}
