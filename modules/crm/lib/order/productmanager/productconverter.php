<?php

namespace Bitrix\Crm\Order\ProductManager;

/**
 * Product fields converter `sale` to `crm` or vice versa.
 */
interface ProductConverter
{
	/**
	 * Convert `b_crm_product_row` table values to `b_sale_basket`.
	 *
	 * @param array $product
	 *
	 * @return array
	 */
	public function convertToSaleBasketFormat(array $product): array;

	/**
	 * Convert `b_sale_basket` table values to `b_crm_product_row`.
	 *
	 * @param array $basketItem
	 *
	 * @return array
	 */
	public function convertToCrmProductRowFormat(array $basketItem): array;
}