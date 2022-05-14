<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Company;
use Bitrix\Crm\Order\ContactCompanyBinding;
use Bitrix\Crm\Order\ContactCompanyEntity;
use Bitrix\Crm\Order\Order;
use Bitrix\Main\Result;

class CompanyToOrder extends ContactCompanyToOrder
{
	/**
	 * Returns a company entity.
	 * If there is no company bound to the order, creates a new one
	 *
	 * @param Order $order
	 *
	 * @return Company
	 */
	protected function getEntity(Order $order): ContactCompanyEntity
	{
		$collection = $order->getContactCompanyCollection();

		$company = $collection->getCompany() ?? $collection->createCompany();

		$company->setField('IS_PRIMARY', 'Y');

		return $company;
	}

	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		(new ContactCompanyBinding(\CCrmOwnerType::Company))->rebind(
			$fromItem->getEntityId(),
			$toItem->getEntityId()
		);

		return new Result();
	}
}
