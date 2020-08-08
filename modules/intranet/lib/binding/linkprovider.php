<?php
namespace Bitrix\Intranet\Binding;

use \Bitrix\Rest\Marketplace\Url;

class LinkProvider
{
	/**
	 * Link provider for REST Applications.
	 * @param array $item Menu item.
	 * @param array $context Context data.
	 * @return string
	 */
	protected static function provideMarketplace(array $item, array $context = []): string
	{
		static $restIncluded = null;

		if ($restIncluded === null)
		{
			$restIncluded = \Bitrix\Main\Loader::includeModule('rest');
		}
		if ($restIncluded)
		{
			return Url::getApplicationPlacementUrl(
				$item['params']['placement_id'],
				$context
			);
		}
		return '';
	}

	/**
	 * Applies specific link provider for menu item.
	 * @param array $item Menu item.
	 * @param array $context Context data.
	 * @return array
	 */
	public static function provide(array $item, array $context = []): array
	{
		if (isset($item['linkProvider']))
		{
			switch ($item['linkProvider'])
			{
				case 'marketplace':
					$item['href'] = self::provideMarketplace($item, $context);
					break;
			}
		}
		return $item;
	}
}