<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EcommerceDocumentsList;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

class FinalSummary extends Configurable
{
	public function getType(): string
	{
		return 'FinalSummary';
	}

	public function getTitle(): ?string
	{
		$contextEntityTypeId = $this->getContext()->getEntityTypeId();
		$contextTitleMap = $this->isOfFinalSummaryType()
			? [
				\CCrmOwnerType::Deal => Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DEAL_ORDERS_PAYMENT'),
				\CCrmOwnerType::SmartInvoice => Loc::getMessage('CRM_TIMELINE_ECOMMERCE_INVOICE_ORDERS_PAYMENT'),
			]
			: [
				\CCrmOwnerType::Deal => Loc::getMessage('CRM_TIMELINE_ECOMMERCE_DEAL_SUMMARY'),
				\CCrmOwnerType::SmartInvoice => Loc::getMessage('CRM_TIMELINE_ECOMMERCE_INVOICE_SUMMARY'),
			]
		;

		if (isset($contextTitleMap[$contextEntityTypeId]))
		{
			return $contextTitleMap[$contextEntityTypeId];
		}

		return parent::getTitle();
	}

	private function isOfFinalSummaryType(): bool
	{
		return $this->model->getTypeId() === TimelineType::FINAL_SUMMARY;
	}

	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::COMPLETE;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return Logo::getInstance(Logo::LIST_CHECK)
			->createLogo()
		;
	}

	public function getContentBlocks(): ?array
	{
		$historyItemModelResult = $this->getHistoryItemModel()->get('RESULT');

		return [
			'content' => (new EcommerceDocumentsList())
				->setOwnerId($this->getContext()->getEntityId())
				->setOwnerTypeId($this->getContext()->getEntityTypeId())
				->setIsWithOrdersMode($this->isOfFinalSummaryType())
				->setSummaryOptions($historyItemModelResult['TIMELINE_SUMMARY_OPTIONS'] ?? [])
		];
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
