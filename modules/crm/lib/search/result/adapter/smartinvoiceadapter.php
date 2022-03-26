<?php

namespace Bitrix\Crm\Search\Result\Adapter;

use Bitrix\Crm\Item;
use Bitrix\Crm\Search\Result\Adapter;
use Bitrix\Crm\Service\Factory;

class SmartInvoiceAdapter extends Adapter
{
	/** @var Factory */
	private $factory;

	public function __construct(Factory $factory)
	{
		$this->factory = $factory;
	}

	protected function getEntityTypeId(): int
	{
		return $this->factory->getEntityTypeId();
	}

	protected function loadItemsByIds(array $ids): array
	{
		$items = $this->factory->getItemsFilteredByPermissions([
			'select' => [
				Item::FIELD_NAME_TITLE,
				Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER,
				Item::FIELD_NAME_BEGIN_DATE,
				Item::FIELD_NAME_COMPANY_ID,
				Item::FIELD_NAME_CONTACTS,
			],
			'filter' => [
				'@' . Item::FIELD_NAME_ID => $ids,
			],
		]);

		$result = [];
		foreach ($items as $item)
		{
			$result[] = $item->getCompatibleData();
		}

		return $result;
	}

	protected function prepareTitle(array $item): string
	{
		$invoice = $this->createItem($item);

		return ($invoice->getHeading() ?? '');
	}

	protected function prepareSubTitle(array $item): string
	{
		$invoice = $this->createItem($item);

		$descriptions = [];

		if ($invoice->getCompanyId() > 0)
		{
			$companyTitle = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $invoice->getCompanyId());
			if ($companyTitle !== '')
			{
				$descriptions[] = $companyTitle;
			}
		}

		if ($invoice->getPrimaryContact())
		{
			$contactName = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Contact, $invoice->getPrimaryContact()->getId());
			if ($contactName !== '')
			{
				$descriptions[] = $contactName;
			}
		}

		return implode(', ', $descriptions);
	}

	private function createItem(array $compatibleData): Item
	{
		$invoice = $this->factory->createItem();

		$invoice->setFromCompatibleData($compatibleData);

		return $invoice;
	}

	protected function areMultifieldsSupported(): bool
	{
		return false;
	}
}
