<?php

namespace Bitrix\BIConnector\Integration\Report;

use Bitrix\Main\Localization\Loc;
use Bitrix\Report\ReportTable;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\AnalyticBoardBatch;

class EventHandler
{
	const BATCH_GROUP_BI_GENERAL = 'crm_general';

	const BATCH_BI = 'bi';

	protected static $boards = null;

	/**
	 * @return AnalyticBoardBatch[]
	 */
	public static function onAnalyticPageBatchCollect()
	{
		$batchList = [];

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
		}

		return $batchList;
	}

	/**
	 * @return AnalyticBoard[]
	 */
	public static function onAnalyticPageCollect()
	{
		if (self::$boards === null)
		{
			self::$boards = static::getBoards();
		}
		return self::$boards;
	}

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
				case 'crm_bi_connect':
					$title = Loc::getMessage('BIC_CRM_MENU_CONNECT');
					break;
				case 'crm_bi_dashboard_manage':
					$title = Loc::getMessage('BIC_CRM_MENU_DASHBOARD_MANAGE');
					break;
				case 'crm_bi_key':
					$title = Loc::getMessage('BIC_CRM_MENU_KEY_MANAGE');
					break;
				default:
					$title = '';
					break;
				}
			}

			$page = new AnalyticBoard();
			$page->setBatchKey(self::BATCH_BI);
			$page->setBoardKey($menuItem['id']);
			$page->setTitle(htmlspecialcharsBx($title));
			$page->setExternal(true);
			$page->setExternalUrl($menuItem['url']);
			$page->setSliderSupport(true);
			$page->setGroup(self::BATCH_GROUP_BI_GENERAL);
			$pageList[] = $page;
		}

		return $pageList;
	}
}
