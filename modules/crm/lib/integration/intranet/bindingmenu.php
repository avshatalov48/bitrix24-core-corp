<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BindingMenu
{
	/**
	 * Returns menu items for different binding places in Intranet.
	 * @param \Bitrix\Main\Event $event Event instance.
	 * @return array
	 */
	public static function onBuildBindingMenu(\Bitrix\Main\Event $event)
	{
		$scriptItems = self::getScriptItems();

		//other stuff $otherItems...

		return $scriptItems;//[...$scriptItems, ...$otherItems]; PHP 7.4
	}

	private static function getScriptItems(): array
	{
		$items = [];
		if (
			Option::get('crm', 'tmp_smart_scripts', 'N') !== 'Y'
			|| !Loader::includeModule('bizproc')
			|| !\CBPRuntime::isFeatureEnabled()
			|| !method_exists(\Bitrix\Bizproc\Script\Manager::class, 'getListByDocument')
		)
		{
			return $items;
		}

		foreach (['crm_switcher', 'crm_detail'] as $placement)
		{
			foreach (['lead', 'deal',] as $entity)
			{
				$docType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::ResolveID($entity));
				$docTypeParam = '\''.\CUtil::JSEscape(\CBPDocument::signDocumentType($docType)).'\'';
				$placementParam = '\''.\CUtil::JSEscape($placement).'\'';

				$scriptItems = \Bitrix\Bizproc\Script\Manager::getListByDocument($docType);
				$scriptItems = array_slice($scriptItems, 0, 10);
				$sort = 0;
				$scriptItems = array_map(
					function ($item) use (&$sort, $placement, $entity)
					{
						$placementParam = '\''.\CUtil::JSEscape($placement).':'.\CUtil::JSEscape($entity).'\'';
						return [
							'id' => 'script_'.$item['ID'],
							'text' => $item['NAME'],
							'onclick' => 'BX.Bizproc.Script.Manager.Instance.startScript('.$item['ID'].", {$placementParam})",
							'sort' => ++$sort,
						];
					},
					$scriptItems
				);

				if ($scriptItems)
				{
					$scriptItems[] = ['delimiter' => true];
					$scriptItems[] = [
						'id' => 'script_list',
						'text' => Loc::getMessage('CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT_LIST'),
						'onclick' => "BX.Bizproc.Script.Manager.Instance.showScriptList({$docTypeParam}, {$placementParam})",
						'sort' => 100
					];
					$scriptItems[] = ['delimiter' => true];
				}

				$scriptItems[] = [
					'id' => 'script_create',
					'text' => Loc::getMessage('CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT_CREATE'),
					'onclick' => "BX.Bizproc.Script.Manager.Instance.createScript({$docTypeParam}, {$placementParam})",
					'sort' => 101
				];
				$scriptItems[] = [
					'id' => 'script_marketplace',
					'text' => Loc::getMessage('CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT_MARKETPLACE'),
					'onclick' => "BX.Bizproc.Script.Market.Instance.showForPlacement({$placementParam})",
					'sort' => 102
				];

				$items[] = [
					'bindings' =>
						[
							$placement => ['include' => [$entity]]
						],
					'items' => [
						[
							'id' => 'script_root',
							'system' => true,
							'text' => Loc::getMessage("CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT"),
							'items' => $scriptItems
						],
					]
				];
			}
		}

		return $items;
	}
}
