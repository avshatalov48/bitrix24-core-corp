<?php
namespace Bitrix\Crm\Component\EntityDetails;

class ProductList
{
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
}