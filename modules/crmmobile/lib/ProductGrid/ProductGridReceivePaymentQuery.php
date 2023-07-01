<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\ProductManager;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteBasketFields;

class ProductGridReceivePaymentQuery extends ProductGridQuery
{
	protected function getEnrichers(): array
	{
		return [
			...parent::getEnrichers(),
			new CompleteBasketFields($this->entity),
		];
	}

	protected function getSummaryQuery(array $products): SummaryQuery
	{
		return new SummaryQuery($this->entity, $products, $this->currencyId, ['addDeliveryToTotal' => false]);
	}
}
