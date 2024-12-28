<?php

namespace Bitrix\Crm\UI\Tools;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Buttons\JsEvent;
use Bitrix\UI\Buttons\JsHandler;
use CCrmOwnerType;

class ToolBar
{
	private const KANBAN_SETTINGS_TOOLBAR_IDS = [
		'toolbar_lead_list',
		'toolbar_deal_list',
		'toolbar_order_kanban',
		'toolbar_quote_list'
	];

	public static function mapItems(array $inputItems, string $toolbarId = null, array $params = []): array
	{
		$inputItems = array_filter($inputItems);

		if (empty($inputItems))
		{
			return [];
		}

		$result = array_map(static function ($item)
		{
			$item = array_change_key_case($item);

			if (isset($item['separator']))
			{
				$item['delimiter'] = $item['separator'];
				unset($item['separator']);
			}

			if (isset($item['name']))
			{
				$item['html'] = htmlspecialcharsbx($item['name']);
				unset($item['name']);

				if (isset($item['counter']) && $item['counter'] > 0)
				{
					$item['html'] = sprintf(
						'%s <span class="main-buttons-item-counter">%d</span>',
						$item['html'],
						$item['counter']
					);
				}
			}

			if (isset($item['link']))
			{
				$item['href'] = $item['link'];
				unset($item['link']);
			}

			if (isset($item['url']))
			{
				$item['href'] = $item['url'];
				unset($item['url']);
			}

			if (isset($item['onclick']))
			{
				$item['onclick'] = new JsCode($item['onclick']);
			}

			if (isset($item['handler']))
			{
				$item['onclick'] = new JsHandler($item['handler']);
			}

			if (isset($item['jsevent']))
			{
				$item['onclick'] = new JsEvent($item['jsevent']);
			}

			if (isset($item['class_name']))
			{
				$item['className'] = htmlspecialcharsbx($item['class_name']);
			}

			if (isset($item['items']))
			{
				$item['items'] = self::mapItems($item['items']);
			}

			return $item;
		}, $inputItems);

		if (isset($toolbarId) && in_array($toolbarId, self::KANBAN_SETTINGS_TOOLBAR_IDS))
		{
			Container::getInstance()->getLocalization()->loadKanbanMessages();

			[$prefix, $entityName] = explode('_', $toolbarId);
			$entityTypeId = CCrmOwnerType::ResolveID($entityName);

			$isKanbanView = self::isCurrentViewKanbanView($entityTypeId);

			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $factory->isCategoriesEnabled())
			{
				$isKanbanView = $isKanbanView && !is_null($params['CATEGORY_ID'] ?? null);
			}

			if (!empty($entityTypeId) && $isKanbanView)
			{
				$result = array_merge([self::getKanbanSettings()], $result);
			}
		}

		return $result;
	}

	public static function getKanbanSettings(): array
	{
		return [
			'id' => 'crm-kanban-settings-sub-menu',
			'text' => Loc::getMessage('CRM_KANBAN_SETTINGS_TITLE'),
			'items' => [
				// a 'sort' item could be added dynamically on frontend
				// see crm.kanban.sort extension
				[
					'text' => Loc::getMessage('CRM_KANBAN_SETTINGS_FIELDS_VIEW'),
					'onclick' => new JsEvent('crm-kanban-settings-fields-view'),
				],
				[
					'text' => Loc::getMessage('CRM_KANBAN_SETTINGS_FIELDS_EDIT'),
					'onclick' => new JsEvent('crm-kanban-settings-fields-edit'),
				],
			]
		];
	}

	private static function isCurrentViewKanbanView(int $entityTypeId): bool
	{
		$currentView = Container::getInstance()
			->getRouter()
			->getCurrentListView($entityTypeId)
		;

		$kanbanViewTypes = [
			Router::LIST_VIEW_KANBAN,
			Router::LIST_VIEW_ACTIVITY,
			Router::LIST_VIEW_DEADLINES
		];
		return in_array($currentView, $kanbanViewTypes, true);
	}

}
