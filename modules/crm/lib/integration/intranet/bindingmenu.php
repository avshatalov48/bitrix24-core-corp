<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Automation;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Intranet;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class BindingMenu
{
	protected const MODULE_ID = 'crm';

	/**
	 * Clear cache of the menu button that contains knowledge base, rest placements, smart scripts, etc.
	 * In most of the cases, this menu is rendered by 'intranet.binding.menu' component
	 */
	public static function clearCache(): void
	{
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			Application::getInstance()->getTaggedCache()->clearByTag('intranet_menu_binding');
		}
	}

	/**
	 * Returns prepared params for a grid's row context actions.
	 * May contain binding to knowledge base, rest placement apps, etc.
	 *
	 * @param int $entityTypeId
	 *
	 * @return array
	 */
	public static function getGridContextActions(int $entityTypeId): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		$menuItems = Intranet\Binding\Menu::getMenuItems(
			BindingMenu\SectionCode::GRID_CONTEXT_ACTIONS,
			BindingMenu\CodeBuilder::getMenuCode($entityTypeId),
			[
				'inline' => true,
			],
		);

		// for render multi-layer menu in grid actions,
		// you need to use key 'menu' instead of 'items', that is used in PopupMenu
		$menuItems['menu'] = $menuItems['items'] ?? null;

		return [
			['delimiter' => true],
			$menuItems,
			['delimeter' => true],
		];
	}

	public static function onBuildBindingMap(Event $event): EventResult
	{
		if (!static::isBindingMapAPIAvailable())
		{
			return new EventResult(EventResult::ERROR, null, static::MODULE_ID);
		}

		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		$sections = static::getMapSections();

		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			foreach ($sections as $section)
			{
				$mapItem = new Intranet\Binding\Map\MapItem(
					BindingMenu\CodeBuilder::getMapItemCode($type->getEntityTypeId()),
					BindingMenu\CodeBuilder::getRestPlacementCode($section->getCode(), $type->getEntityTypeId()),
				);

				$section->add($mapItem);
			}
		}

		if (InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			foreach ($sections as $section)
			{
				if ($section === \Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode::TUNNELS)
				{
					continue;
				}
				$mapItem = new Intranet\Binding\Map\MapItem(
					BindingMenu\CodeBuilder::getMapItemCode(\CCrmOwnerType::SmartInvoice),
					BindingMenu\CodeBuilder::getRestPlacementCode($section->getCode(), \CCrmOwnerType::SmartInvoice),
				);

				$section->add($mapItem);
			}
		}

		return new EventResult(
			EventResult::SUCCESS,
			[
				'map' => new Intranet\Binding\Map($sections),
			],
			static::MODULE_ID,
		);
	}

	/**
	 * This method is used to avoid inter-module dependency on intranet. Feel free to remove if it is no longer needed
	 *
	 * @return bool
	 */
	protected static function isBindingMapAPIAvailable(): bool
	{
		return (
			Loader::includeModule('intranet')
			&& class_exists(Intranet\Binding\Map::class)
			&& class_exists(Intranet\Binding\Map\MapSection::class)
			&& class_exists(Intranet\Binding\Map\MapItem::class)
		);
	}

	/**
	 * @return Intranet\Binding\Map\MapSection[]
	 */
	protected static function getMapSections(): array
	{
		if (!static::isBindingMapAPIAvailable())
		{
			return [];
		}

		$sections = [];
		foreach (BindingMenu\SectionCode::getAll() as $sectionCode)
		{
			$sections[] = new Intranet\Binding\Map\MapSection(static::MODULE_ID, $sectionCode);
		}

		return $sections;
	}

	/**
	 * Returns menu items for different binding places in Intranet.
	 *
	 * @param Event $event Event instance.
	 *
	 * @return array
	 */
	public static function onBuildBindingMenu(Event $event)
	{
		$scriptItems = self::getScriptItems();

		//other stuff $otherItems...

		return $scriptItems;//[...$scriptItems, ...$otherItems]; PHP 7.4
	}

	private static function getScriptItems(): array
	{
		$items = [];
		if (!Loader::includeModule('bizproc'))
		{
			return $items;
		}

		$marketUrl = null;
		$manifestCode = 'crm_smart_robots';
		if (Loader::includeModule('rest'))
		{
			$marketUrl = \Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl($manifestCode);
		}

		$entityList = ['lead', 'deal', 'contact', 'company', 'order', 'smart_invoice'];
		$typesMap = Container::getInstance()->getDynamicTypesMap();
		$typesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		foreach ($typesMap->getTypes() as $type)
		{
			if ($type->getIsAutomationEnabled())
			{
				$entityList[] = strtolower(\CCrmOwnerType::ResolveName($type->getEntityTypeId()));
			}
		}

		foreach ([BindingMenu\SectionCode::SWITCHER, BindingMenu\SectionCode::DETAIL] as $placement)
		{
			foreach ($entityList as $entity)
			{
				if (!Automation\Factory::isScriptAvailable(\CCrmOwnerType::ResolveID($entity)))
				{
					continue;
				}

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
							'text' => htmlspecialcharsbx($item['NAME']),
							'onclick' => 'BX.Bizproc.Script.Manager.Instance.startScript('.$item['ID'].", {$placementParam})",
							'sort' => ++$sort,
						];
					},
					$scriptItems
				);

				if ($scriptItems)
				{
					$scriptItems[] = ['delimiter' => true];
				}

				$scriptItems[] = [
					'id' => 'script_list',
					'text' => Loc::getMessage('CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT_LIST'),
					'onclick' => "BX.Bizproc.Script.Manager.Instance.showScriptList({$docTypeParam}, '{$manifestCode}')",
					'sort' => 100
				];
				$scriptItems[] = ['delimiter' => true];

				$scriptItems[] = [
					'id' => 'script_create',
					'text' => Loc::getMessage('CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT_CREATE'),
					'onclick' => "BX.Bizproc.Script.Manager.Instance.createScript({$docTypeParam}, {$placementParam})",
					'sort' => 101
				];

				if ($marketUrl)
				{
					$scriptItems[] = [
						'id' => 'script_marketplace',
						'text' => Loc::getMessage('CRM_INTEGRATION_INTRANET_MENU_SMART_SCRIPT_MARKETPLACE'),
						'href' => $marketUrl,
						'sort' => 102
					];
				}

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
