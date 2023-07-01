<?php

namespace Bitrix\CalendarMobile;

use Bitrix\Main\Result;

class JSComponent
{
	static public function isUsed(): bool
	{
		return true;
	}

	static public function enable(): Result
	{
		JSComponent::clearCache();
		\Bitrix\Main\Config\Option::set('calendarmobile', 'jscomponents', 'Y');

		return new Result();
	}

	static public function disable(): Result
	{
		JSComponent::clearCache();
		\Bitrix\Main\Config\Option::set('calendarmobile', 'jscomponents', 'N');

		return new Result();
	}

	static protected function clearCache()
	{
		$cacheManager = JSComponent::getCacheManager();
		$cacheManager->ClearByTag('mobile_custom_menu');
	}

	static protected function getCacheManager()
	{
		global $CACHE_MANAGER;
		return $CACHE_MANAGER;
	}
}