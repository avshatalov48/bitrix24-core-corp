<?php

namespace Bitrix\Market;

use Bitrix\Main\Application;
use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Rest\Transport;

class Categories
{
	private const CACHE_ID = 'rest|market|categories|' . LANGUAGE_ID;

	private static array $list = [];

	public static function get(): array
	{
		return Categories::$list;
	}

	public static function set(array $categories)
	{
		Categories::$list = $categories;
	}

	public static function initFromCache()
	{
		$managedCache = Application::getInstance()->getManagedCache();
		if ($managedCache->read(86400, Categories::CACHE_ID)) {
			$cacheResult = $managedCache->get(Categories::CACHE_ID);
			if (is_array($cacheResult)) {
				Categories::set($cacheResult);
			}
		}
	}

	public static function saveCache(array $result)
	{
		if (empty($result)) {
			return;
		}

		$result['ITEMS'] = array_values($result['ITEMS']);
		$managedCache = Application::getInstance()->getManagedCache();
		$managedCache->set(Categories::CACHE_ID, $result);
		Categories::set($result);
	}

	public static function forceGet(): array
	{
		if (empty(Categories::get())) {
			$response = Transport::instance()->call(Actions::METHOD_GET_CATEGORIES_V2);
			if (!empty($response)) {
				Categories::saveCache($response);
			}
		}

		return Categories::get();
	}
}