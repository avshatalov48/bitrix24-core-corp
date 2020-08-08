<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\ShopReports;

use Bitrix\Crm\Integration\Report\Handler\Order\BuyersGrid;
use Bitrix\Crm\Integration\Report\View;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;

Loc::loadMessages(__FILE__);
/**
 * Class SalesOrderBuyerBoard
 * @package Bitrix\Crm\Integration\Report\Dashboard\ShopReports
 */
class SalesOrderBuyerBoard
{
	const VERSION = 'v1';
	const BOARD_KEY = 'crm_sales_buyer';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(static::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(2);
		$salesBuyersGrid = static::buildBuyerStatisticGrid();
		$salesBuyersGrid->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($salesBuyersGrid);

		$board->addRows($firstRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	protected static function buildBuyerStatisticGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\ShopReports\SaleBuyersGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage("CRM_REPORT_ORDER_RATING_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$sumReport = new Report();
		$sumReport->setGId(Util::generateUserUniqueId());
		$sumReport->setReportClassName(BuyersGrid::getClassName());
		$sumReport->setWidget($widget);
		$sumReport->addConfigurations($sumReport->getReportHandler(true)->getConfigurations());
		$widget->addReports($sumReport);

		return $widget;
	}
}