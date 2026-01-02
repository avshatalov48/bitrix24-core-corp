<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Result;
use Bitrix\Main\ORM\Objectify\Values;
use CCrmOwnerType;

final class ProductRows extends Field
{
	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		$entityTypeId = $item->getEntityTypeId();
		if (
			!in_array(
				$entityTypeId,
				[CCrmOwnerType::Lead, CCrmOwnerType::Deal, CCrmOwnerType::Quote, CCrmOwnerType::SmartInvoice],
				true,
			)
		)
		{
			return new Result();
		}

		$productRowsCollection = $item->getProductRows();
		if (!$productRowsCollection)
		{
			return new Result();
		}

		$productRows = $productRowsCollection->toArray();
		$originalProductRows = array_filter(
			$productRowsCollection->toArray(Values::ACTUAL),
			static fn($originalProductRow) => isset($originalProductRow['ID']),
		);

		return Container::getInstance()->getProductRowChecker()->checkCatalogRights(
			$entityTypeId,
			$productRows,
			$item->getCurrencyId(),
			$originalProductRows,
			$item->getData(Values::ACTUAL)['CURRENCY_ID'] ?? null,
		);
	}

	protected function processLogic(Item $item, Context $context = null): Result
	{
		return $item->normalizeProductRows();
	}
}
