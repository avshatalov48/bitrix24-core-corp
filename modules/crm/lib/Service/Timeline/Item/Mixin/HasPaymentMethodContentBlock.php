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
trait HasPaymentMethodContentBlock
{
	public function getPaymentMethodContentBlock(): LineOfTextBlocks
	{
		$result = new LineOfTextBlocks();

		if ($this->getModel()->getAssociatedEntityTypeId() !== \CCrmOwnerType::OrderPayment)
		{
			return $result;
		}

		$result
			->addContentBlock(
				'title',
				(new Text())
					->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PAYMENT_METHOD'))
					->setFontSize(Text::FONT_SIZE_SM)
					->setColor(Text::COLOR_BASE_70)
			)
			->addContentBlock(
				'value',
				(new Text())
					->setValue((string)$this->getAssociatedEntityModel()->get('PAY_SYSTEM_NAME'))
					->setColor(Text::COLOR_BASE_90)
			)
		;

		return $result;
	}

	abstract public function getModel(): Model;

	abstract protected function getAssociatedEntityModel(): ?AssociatedEntityModel;
}
