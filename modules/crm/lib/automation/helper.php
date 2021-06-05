<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);
Extension::load(['crm.router']);

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
			if ($entityTypeId === \CCrmOwnerType::Quote)
			{
				if (!QuoteSettings::getCurrent()->isFactoryEnabled())
				{
					return [];
				}
				$quoteFactory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
				if (!$quoteFactory || !$quoteFactory->isAutomationEnabled())
				{
					return [];
				}
			}
			$url = '/crm/'.mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)).'/automation/'.$categoryId.'/';
			if ($entityTypeId === \CCrmOwnerType::Order)
			{
				//TODO: crazy shop
				$url = '/shop/orders/automation/0/';
			}
			elseif ($entityTypeId === \CCrmOwnerType::Quote)
			{
				$url =
					"javascript:BX.Crm.Router.openSlider('"
					. \CUtil::JSUrlEscape(Container::getInstance()->getRouter()->getAutomationUrl($entityTypeId))
					. "')"
				;
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
