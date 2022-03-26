<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmControlPanel extends CBitrixComponent
{
	public function createMenuTree($standardItems)
	{
		return $this->createMenuItems($this->getMap(), $standardItems);
	}

	private function getMap(): array
	{
		return [
			[
				'ID' => 'LEAD',
			],
			[
				'ID' => 'DEAL',
			],
			[
				'ID' => 'crm_catalogue',
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_STORE_DOCS'),
				'SUB_ITEMS' => [
					['ID' => 'CATALOGUE'],
					['ID' => 'STORE_DOCUMENTS'],
				],
			],
			[
				'ID' => 'crm_clients',
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CLIENTS'),
				'SUB_ITEMS' => [
					['ID' => 'CONTACT'],
					['ID' => 'COMPANY'],
					['ID' => 'CONTACT_CENTER', 'SLIDER_MODE' => true],
				],
			],
			[
				'ID' => 'crm_sales',
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SALES'),
				'SUB_ITEMS' => [
					['ID' => 'SMART_INVOICE'],
					['ID' => 'INVOICE'],
					['ID' => 'QUOTE'],
					['ID' => 'SALES_CENTER', 'SLIDER_MODE' => true],
				],
			],
			[
				'ID' => 'crm_analytics',
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS'),
				'URL' => '',
				'SUB_ITEMS' => [
					['ID' => 'ANALYTICS_SALES_FUNNEL'],
					['ID' => 'ANALYTICS_MANAGERS'],
					['ID' => 'ANALYTICS_CALLS'],
					['ID' => 'ANALYTICS_DIALOGS', 'SLIDER_MODE' => true],
					['ID' => 'CRM_TRACKING', 'SLIDER_MODE' => true],
					['ID' => 'REPORT'],
				],
			],
			[
				'ID' => 'crm_integrations',
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_INTEGRATIONS'),
				'URL' => '',
				'SUB_ITEMS' => [
					['ID' => 'TELEPHONY', 'SLIDER_MODE' => true],
					['ID' => 'MAIL', 'SLIDER_MODE' => true],
					['ID' => 'MESSENGERS', 'SLIDER_MODE' => true],
					['ID' => 'SITEBUTTON'],
					['ID' => 'WEBFORM'],
					['ID' => 'CONTACT_CENTER', 'SLIDER_MODE' => true],
					['ID' => 'MARKETPLACE', 'SLIDER_MODE' => true],
					['ID' => 'MARKETPLACE_CRM_MIGRATION', 'SLIDER_MODE' => true],
					['ID' => 'ONEC', 'SLIDER_MODE' => true],
					['ID' => 'MARKETPLACE_CRM_SOLUTIONS', 'SLIDER_MODE' => true],
					['ID' => 'DEVOPS', 'SLIDER_MODE' => true],
				],
			],
			[
				'ID' => 'crm_settings',
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'),
				'SUB_ITEMS' => [
					['ID' => 'SETTINGS'],
					['ID' => 'MY_COMPANY'],
					['ID' => 'PERMISSIONS'],
					['ID' => 'SALES_CENTER_PAYMENT', 'SLIDER_MODE' => true],
					['ID' => 'SALES_CENTER_DELIVERY', 'SLIDER_MODE' => true],
					[
						'ID' => 'DYNAMIC_ADD',
					],
				],
			],
			[
				'ID' => 'RECYCLE_BIN',
			],
			[
				'ID' => 'EVENT',
			],
		];
	}

	private function createMenuItems($mapItems, $standardItems): array
	{
		$result = [];
		foreach ($mapItems as $mapItem)
		{
			if (!is_array($mapItem))
			{
				continue;
			}

			$item = $standardItems[$mapItem['ID']] ?? $mapItem;
			if (!isset($item['NAME']) && !$item['TEXT'])
			{
				continue;
			}

			if (empty($mapItem['SUB_ITEMS']) && empty($item['SUB_ITEMS']))
			{
				$item['IS_ACTIVE'] = $this->arParams["ACTIVE_ITEM_ID"] === $item['ID'];
				$item['TEXT'] = $item['TEXT'] ?? $item['NAME'];

				if (isset($mapItem['SLIDER_MODE']) && $mapItem['SLIDER_MODE'] === true)
				{
					$item['ON_CLICK'] = 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($item['URL']) . '");';
					$item['ON_CLICK'] .= 'return false;';
				}

				if (isset($item['SLIDER_ONLY']) && $item['SLIDER_ONLY'] === true)
				{
					$item['URL'] = '';
				}

				$result[] = $item;
			}
			else
			{
				$subItems = $this->createMenuItems($mapItem['SUB_ITEMS'] ?? $item['SUB_ITEMS'], $standardItems);
				if (!empty($subItems))
				{
					//$firstSubItem = $subItems[0];
					$result[] = [
						'IS_ACTIVE' => $this->arParams["ACTIVE_ITEM_ID"] === $item['ID'],
						'ID' => $item['ID'],
						'TEXT' => $item['TEXT'] ?? $item['NAME'],
						'ITEMS' => $subItems,
						// 'URL' => $item['URL'] ?? $firstSubItem['URL'],
						// 'ON_CLICK' => $item['ON_CLICK'] ?? $firstSubItem['ON_CLICK'],
					];
				}
			}
		}

		return $result;
	}

	protected function createFileMenuItems($items, $depthLevel = 1): array
	{
		$result = [];
		foreach ($items as $item)
		{
			$hasChildren = isset($item['ITEMS']) && is_array($item['ITEMS']) && !empty($item['ITEMS']);

			$result[] = [
				$item['NAME'] ?? $item['TEXT'],
				$item['URL'],
				[],
				[
					'DEPTH_LEVEL' => $depthLevel,
					'FROM_IBLOCK' => true,
					'IS_PARENT' => $hasChildren,
					'ON_CLICK' => $item['ON_CLICK'] ?? null,
				]
			];

			if ($hasChildren)
			{
				$result = array_merge($result, $this->createFileMenuItems($item['ITEMS'], $depthLevel + 1));
			}
		}

		return $result;
	}
}