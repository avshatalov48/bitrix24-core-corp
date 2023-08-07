<?php
namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Main\Grid\Options;

class ProductList
{
	public const DEFAULT_GRID_ID = 'CCrmEntityProductListComponent';

	/**
	 * @param array $options
	 * @param string $queryString
	 * @return string
	 */
	public static function getComponentUrl(array $options = [], string $queryString = ''): string
	{
		return static::getUrl(
			'/bitrix/components/bitrix/crm.entity.product.list/class.php',
			$options,
			$queryString
		);
	}

	/**
	 * @param array $options
	 * @param string $queryString
	 * @return string
	 */
	public static function getLoaderUrl(array $options = [], string $queryString = ''): string
	{
		return static::getUrl(
			'/bitrix/components/bitrix/crm.entity.product.list/lazyload.ajax.php',
			$options,
			$queryString
		);
	}

	/**
	 * @param string $path
	 * @param array $options
	 * @param string $queryString
	 * @return string
	 */
	protected static function getUrl(string $path, array $options = [], string $queryString = ''): string
	{
		if (!empty($options))
		{
			$params = [];
			foreach ($options as $name => $value)
			{
				$params[] = urlencode($name).'='.urlencode($value);
			}
			$path .= '?'.implode('&', $params);
			unset($name, $value, $params);
		}
		if ($queryString !== '')
		{
			$path .= (!empty($options) ? '&' : '?').$queryString;
		}
		return $path;
	}

	/**
	 * Map of showing columns in product grid of CRM entity card by default
	 *
	 * @return array
	 */
	public static function getHeaderDefaultMap(): array
	{
		return [
			'MAIN_INFO' => true,
			'PRICE' => true,
			'QUANTITY' => true,
			'DISCOUNT_PRICE' => false,
			'DISCOUNT_ROW' => false,
			'TAX_RATE' => false,
			'TAX_INCLUDED' => false,
			'TAX_SUM' => false,
			'STORE_INFO' => true,
			'STORE_AVAILABLE' => true,
			'RESERVE_INFO' => true,
			'ROW_RESERVED' => true,
			'DEDUCTED_INFO' => true,
			'SUM' => true,
		];
	}

	/**
	 * Add headers to product grid of CRM entity card
	 *
	 * @param array $headers
	 * @return void
	 */
	public static function addGridHeaders(array $headers): void
	{
		$options = new Options(self::DEFAULT_GRID_ID);
		$allUsedColumns = $options->getUsedColumns();

		$allHeaderMap = self::getHeaderDefaultMap();

		if (empty($allUsedColumns))
		{
			$defaultHeaders = array_filter($allHeaderMap);
			$allUsedColumns = array_keys($defaultHeaders);
		}

		// sort new columns by default grid column sort
		$allHeaders = array_keys($allHeaderMap);
		$currentHeadersInDefaultPosition = array_values(
			array_intersect($allHeaders, array_merge($allUsedColumns, $headers))
		);
		$headers = array_values(array_intersect($allHeaders, $headers));

		foreach ($headers as $header)
		{
			$insertPosition = array_search($header, $currentHeadersInDefaultPosition, true);
			array_splice($allUsedColumns, $insertPosition, 0, $header);
		}

		$options->setColumns(implode(',', $allUsedColumns));
		$options->save();
	}

	/**
	 * Remove headers from product grid of CRM entity card
	 *
	 * @param $headers
	 * @return void
	 */
	public static function removeGridHeaders(array $headers): void
	{
		$options = new Options(self::DEFAULT_GRID_ID);
		$allUsedColumns = $options->getUsedColumns();

		if (empty($allUsedColumns))
		{
			$defaultHeaders = array_filter(self::getHeaderDefaultMap());
			$allUsedColumns = array_keys($defaultHeaders);
		}

		$allUsedColumns = array_diff($allUsedColumns, $headers);
		$options->setColumns(implode(',', $allUsedColumns));
		$options->save();
	}
}
