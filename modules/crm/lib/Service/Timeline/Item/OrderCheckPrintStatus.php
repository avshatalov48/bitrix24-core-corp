<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Item\Interfaces;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Layout;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

abstract class OrderCheckPrintStatus extends Configurable implements Interfaces\HasCheckDetails
{
	use Mixin\HasCheckDetails;

	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::CHECK;
	}

	public function getTitle(): ?string
	{
		return
			Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_NAME')
			. ' "' . $this->getAssociatedEntityModel()->get('NAME') . '"'
		;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return Logo::getInstance(Logo::LIST_CHECK)
			->createLogo()
		;
	}

	public function getContentBlocks(): ?array
	{
		return [
			'details' =>
				(new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_NAME'))
					->setInline()
					->setContentBlock(
						$this->getCheckTitleContentBlock()
					)
			,
			'contentCashBox' =>
				(new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CASHBOX'))
					->setInline()
					->setContentBlock(
						(new Layout\Body\ContentBlock\Text())
							->setValue($this->getAssociatedEntityModel()->get('CASHBOX_NAME')),
					)
			,
		];
	}

	public function getButtons(): ?array
	{
		$result = [
			'open' => (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_OPEN'),
				Layout\Footer\Button::TYPE_SECONDARY
			))
				->setAction($this->getOpenCheckAction()),
		];

		$checkInFiscalDataOperatorAction = $this->getCheckInFiscalDataOperatorAction();
		if ($checkInFiscalDataOperatorAction)
		{
			$result['checkInFiscalDataOperator'] =
				(new Layout\Footer\Button(
					Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_AT_FISCAL_DATA_OPERATOR'),
					Layout\Footer\Button::TYPE_SECONDARY
				))
					->setAction($checkInFiscalDataOperatorAction)
			;
		}

		return $result;
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
