<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteBasketFieldsForNewProduct;

class SingleProductCreatePaymentQuery extends SingleProductQuery
{
	protected function getEnrichers(): array
	{
		return [
			...parent::getEnrichers(),
			new CompleteBasketFieldsForNewProduct($this->entity),
		];
	}
}
