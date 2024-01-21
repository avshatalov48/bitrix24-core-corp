<?php

namespace Bitrix\BIConnector\Integration\Report;

use Bitrix\Main\Localization\Loc;
use Bitrix\Report\ReportTable;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\AnalyticBoardBatch;
use Bitrix\Report\VisualConstructor\BoardButton;

class EventHandler
{
	const BATCH_GROUP_BI_GENERAL = 'crm_general';

	const BATCH_BI = 'bi';
	const BATCH_BI_SETTINGS = 'bi_settings';

	protected static $boards = null;

	/**
	 * Event onAnalyticPageBatchCollect handler.
	 *
	 * @return AnalyticBoardBatch[]
	 */
	public static function onAnalyticPageBatchCollect()
	{
		$batchList = [];
		global $USER;

		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled('biconnector'))
			{
				return $batchList;
			}
		}

		if (static::onAnalyticPageCollect())
		{
			$bi = new AnalyticBoardBatch();
			$bi->setKey(self::BATCH_BI);
			$bi->setTitle(Loc::getMessage('BIC_CRM_MENU_ITEM'));
			$bi->setOrder(160);
			$bi->setGroup(self::BATCH_GROUP_BI_GENERAL);
			$batchList[] = $bi;

			if ($USER->CanDoOperation('biconnector_dashboard_manage'))
			{
				$biSettings = new AnalyticBoardBatch();
				$biSettings->setKey(self::BATCH_BI_SETTINGS);
				$biSettings->setTitle(Loc::getMessage('BIC_CRM_MENU_ITEM_SETTINGS'));
				$biSettings->setOrder(160);
				$biSettings->setGroup(self::BATCH_GROUP_BI_GENERAL);
				$batchList[] = $biSettings;
			}
		}

		return $batchList;
	}

	/**
	 * Event onAnalyticPageCollect handler.
	 *
	 * @return \Bitrix\Report\VisualConstructor\AnalyticBoard[]
	 */
	public static function onAnalyticPageCollect()
	{
		if (self::$boards === null)
		{
			self::$boards = array_merge(static::getBoards(), static::getSettingsBoards());
		}
		return self::$boards;
	}

	/**
	 * Returns report menu items.
	 *
	 * @return \Bitrix\Report\VisualConstructor\AnalyticBoard[]
	 */
	protected static function getBoards()
	{
		$pageList = [];

		$manager = \Bitrix\BIConnector\Manager::getInstance();
		foreach ($manager->getMenuItems() as $menuItem)
		{
			if (isset($menuItem['title']))
			{
				$title = $menuItem['title'];
			}
			else
			{
				switch ($menuItem['id'])
				{
				case 'crm_bi_templates':
					$title = Loc::getMessage('BIC_CRM_MENU_REPORT_TEMPLATES');
					break;
				case 'crm_microsoft_power_bi':
					$title = Loc::getMessage('BIC_CRM_MENU_MICROSOFT');
					break;
				case 'crm_yandex_datalens':
					$title = Loc::getMessage('BIC_CRM_MENU_YANDEX');
					break;
				case 'crm_google_datastudio':
					$title = Loc::getMessage('BIC_CRM_MENU_GOOGLE');
					break;
				default:
					$title = '';
					break;
				}
			}

			$page = new AnalyticBoard();
			$page->setBatchKey(self::BATCH_BI);
			$page->setBoardKey($menuItem['id']);
			if (!empty($menuItem['component_name']))
			{
				$page->setLimit(
					[
						'NAME' => $menuItem['component_name'],
						'PARAMS' => $menuItem['component_parameters'] ?? []
					],
					true
				);
				$page->addButton(self::getImplementationOrderButton());
			}
			$page->setExternal($menuItem['external'] ?? true);
			$page->setExternalUrl($menuItem['url'] ?? '');
			$page->setSliderSupport(true);
			$page->setTitle($title);
			$page->setGroup(self::BATCH_GROUP_BI_GENERAL);
			$pageList[] = $page;
		}

		return $pageList;
	}

	protected static function getSettingsBoards(): array
	{
		$pageList = [];
		$manager = \Bitrix\BIConnector\Manager::getInstance();

		foreach ($manager->getMenuSettingsItem() as $menuItem)
		{
			$page = new AnalyticBoard();
			$page->setBatchKey(self::BATCH_BI_SETTINGS);
			$page->setBoardKey($menuItem['id']);
			$page->setGroup(self::BATCH_GROUP_BI_GENERAL);
			$page->setExternal($menuItem['external'] ?? true);
			$page->setExternalUrl($menuItem['url'] ?? '');
			$page->setSliderLoader('biconnector:settings-grid');
			$page->setSliderSupport(true);

			if (isset($menuItem['title']))
			{
				$page->setTitle(htmlspecialcharsbx($menuItem['title']));
			}
			else
			{
				switch ($menuItem['id'])
				{
					case 'crm_bi_connect':
						$page->setTitle(Loc::getMessage('BIC_CRM_MENU_CONNECT'));
						break;
					case 'crm_bi_dashboard_manage':
						$page->setTitle(Loc::getMessage('BIC_CRM_MENU_DASHBOARD_MANAGE'));
						break;
					case 'crm_bi_key':
						$page->setTitle(Loc::getMessage('BIC_CRM_MENU_KEY_MANAGE'));
						break;
					case 'crm_bi_usage':
						$page->setTitle( Loc::getMessage('BIC_CRM_MENU_USAGE_STAT'));
						break;
					default:
						$page->setTitle('');
						break;
				}
			}

			$pageList[] = $page;
		}

		return $pageList;
	}

	protected static function getImplementationOrderButton(): BoardButton
	{
		return new BoardButton(
			'<button class="ui-btn ui-btn-themes ui-btn-light-border" onclick="BX.UI.InfoHelper.show(\'info_implementation_request\');">'
			. Loc::getMessage('BIC_CRM_BUTTON_ORDER')
			. '</button>'
		);
	}
}
