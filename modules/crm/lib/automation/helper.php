<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Crm\Settings\InvoiceSettings;
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
		if (!Factory::isAutomationAvailable($entityTypeId))
		{
			return [];
		}
		if ($entityTypeId === \CCrmOwnerType::Quote && !QuoteSettings::getCurrent()->isFactoryEnabled())
		{
			return [];
		}

		if ($entityTypeId === \CCrmOwnerType::SmartInvoice && !InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			return [];
		}

		$categoryId = max(0, $categoryId);
		$url = Container::getInstance()->getRouter()->getAutomationUrl($entityTypeId, $categoryId);

		return [
			[
				'id' => 'automation',
				'name' => Loc::getMessage('CRM_AUTOMATION_HELPER_ROBOT_TITLE'),
				'active' => false,
				'url' => $url
			]
		];
	}

	public static function prepareCompatibleData(int $entityTypeId, array $compatibleData): array
	{
		$entity = \CCrmBizProcHelper::ResolveDocumentName($entityTypeId);

		if ($entity && method_exists($entity, 'prepareCompatibleData'))
		{
			return call_user_func([$entity, 'prepareCompatibleData'], $compatibleData);
		}

		return $compatibleData;
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
