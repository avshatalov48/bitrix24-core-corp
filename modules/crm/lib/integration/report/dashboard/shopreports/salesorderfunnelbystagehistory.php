<?php
namespace Bitrix\Crm\Integration\Report\Dashboard\ShopReports;

use Bitrix\Crm\Integration\Report\Handler\Order\StatusGrid;
use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;

/**
 * Class SalesOrderFunnelBoard
 * @package Bitrix\Crm\Integration\Report\Dashboard\ShopReports
 */

class SalesOrderFunnelByStageHistory  extends SalesOrderFunnelBoard
{
	const VERSION = 'v1';
	const BOARD_KEY = 'crm_sales_order_funnel_by_stage_history';

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
		$firstRow->setWeight(1);
		$funnel = self::buildSalesFunnelByStageHistory();
		$funnel->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($funnel);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$salesFunnelGridByManager = self::buildManagerEfficiencyGrid();
		$salesFunnelGridByManager->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($salesFunnelGridByManager);

		$board->addRows($secondRow);

		return $board;
	}

	private static function buildSalesFunnelByStageHistory()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(ColumnFunnel::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_TITLE'
			)
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'calculateMode',
			ColumnFunnel::CLASSIC_CALCULATE_MODE
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$orderCount = new Report();
		$orderCount->setGId(Util::generateUserUniqueId());
		$orderCount->setReportClassName(StatusGrid::getClassName());
		$orderCount->setWidget($widget);
		$orderCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_ORDER_METRIC_TITLE'
			)
		);
		$orderCount->getReportHandler(true)->updateFormElementValue('disableSuccessStages', true);
		$orderCount->getReportHandler(true)->updateFormElementValue(
			'calculate',
			StatusGrid::WHAT_WILL_CALCULATE_ORDER_DATA_FOR_FUNNEL
		);
		$orderCount->addConfigurations($orderCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($orderCount);

		$successOrderCount = new Report();
		$successOrderCount->setGId(Util::generateUserUniqueId());
		$successOrderCount->setReportClassName(StatusGrid::getClassName());
		$successOrderCount->setWidget($widget);
		$successOrderCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_SUCCESS_ORDER_TITLE'
			)
		);
		$successOrderCount->getReportHandler(true)->updateFormElementValue('color', '#4fc3f7');
		$successOrderCount->getReportHandler(true)->updateFormElementValue('shortMode', true);
		$successOrderCount->getReportHandler(true)->updateFormElementValue('calculate', StatusGrid::WHAT_WILL_CALCULATE_SUCCESS_ORDER_DATA_FOR_FUNNEL);
		$successOrderCount->addConfigurations($successOrderCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($successOrderCount);

		return $widget;
	}

	public static function buildManagerEfficiencyGrid()
	{
		$widget = parent::buildManagerEfficiencyGrid();

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'calculateMode',
			ColumnFunnel::CLASSIC_CALCULATE_MODE
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		return $widget;
	}
}