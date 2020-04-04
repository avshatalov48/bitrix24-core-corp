<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Helper
{
	protected static $isBizprocEnabled;

	public static function isBizprocEnabled()
	{
		if (static::$isBizprocEnabled === null)
		{
			static::$isBizprocEnabled = Loader::includeModule('bizproc');
		}

		return static::$isBizprocEnabled;
	}

	public static function getNavigationBarItems($entityTypeId, $categoryId = 0)
	{
		$categoryId = max(0, $categoryId);
		if (Factory::isAutomationAvailable($entityTypeId))
		{
			$url = '/crm/'.strtolower(\CCrmOwnerType::ResolveName($entityTypeId)).'/automation/'.$categoryId.'/';
			if ($entityTypeId === \CCrmOwnerType::Order)
			{
				//TODO: crazy shop
				$url = '/shop/orders/automation/0/';
			}

			return [
				[
					'id' => 'automation',
					'name' => Loc::getMessage('CRM_AUTOMATION_HELPER_ROBOT_TITLE'),
					'active' => false,
					'url' => $url
				]
			];
		}
		return [];
	}

	public static function __callStatic($name, $arguments)
	{
		if (static::isBizprocEnabled())
		{
			return call_user_func_array([\Bitrix\Bizproc\Automation\Helper::class, $name], $arguments);
		}

		return null;
	}
}