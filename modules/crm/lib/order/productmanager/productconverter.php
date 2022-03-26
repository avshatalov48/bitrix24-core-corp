<?php

namespace Bitrix\Crm\Order\ProductManager;

interface ProductConverter
{
	public function convertToSaleBasketFormat(array $product): array;
}