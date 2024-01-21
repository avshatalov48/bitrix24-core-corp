<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteBasketFields;

class ProductGridCreatePaymentQuery extends ProductGridQuery
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
		return new SummaryQuery(
			$this->entity,
			$products,
			$this->currencyId,
			[
				'addDeliveryToTotal' => false,
			]
		);
	}
}
