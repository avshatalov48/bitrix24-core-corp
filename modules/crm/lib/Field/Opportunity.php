<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;

class Opportunity extends Field
{
	public function isValueEmpty($fieldValue): bool
	{
		return (float)($fieldValue) === 0.0;
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		$products = $itemBeforeSave->getProductRows();
		$areProductsEmpty = is_null($products) || (count($products) <= 0);
		$itemAlwaysHadNoProducts = $areProductsEmpty && !$itemBeforeSave->isChanged(Item::FIELD_NAME_PRODUCTS);

		if ($itemAlwaysHadNoProducts || $item->getIsManualOpportunity())
		{
			$this->syncOpportunityAccount($item, (float)$item->getOpportunity(), $result);

			return $result;
		}

		//after this point opportunity is always in auto mode

		$accounting = Container::getInstance()->getAccounting();

		$price = $accounting->calculateByItem($item)->getPrice();
		$priceWithDelivery = $price + $accounting->calculateDeliveryTotal(ItemIdentifier::createByItem($item));

		$opportunity = $priceWithDelivery;

		$result->setNewValue($this->getName(), $opportunity);
		$this->syncOpportunityAccount($item, (float)$opportunity, $result);

		return $result;
	}

	private function syncOpportunityAccount(Item $item, float $newOpportunityValue, FieldAfterSaveResult $result): void
	{
		if ($item->hasField(Item::FIELD_NAME_OPPORTUNITY_ACCOUNT))
		{
			$opportunityAccount = Currency\Conversion::toAccountCurrency(
				$newOpportunityValue,
				$item->getCurrencyId(),
				$item->hasField(Item::FIELD_NAME_EXCH_RATE) ? $item->getExchRate() : null,
			);

			$result->setNewValue(Item::FIELD_NAME_OPPORTUNITY_ACCOUNT, $opportunityAccount);
		}
	}
}
