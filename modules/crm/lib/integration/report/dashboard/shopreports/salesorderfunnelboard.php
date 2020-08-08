<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\ShopReports;

use Bitrix\Crm\Integration\Report\Handler\Order\StatusGrid;
use Bitrix\Crm\Integration\Report\Handler\Order\ResponsibleGrid;
use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
use Bitrix\Crm\Integration\Report\View\FunnelGrid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;
use Bitrix\Report\VisualConstructor\Views\Component\Grid;

Loc::loadMessages(__FILE__);
/**
 * Class SalesOrderFunnelBoard
 * @package Bitrix\Crm\Integration\Report\Dashboard\ShopReports
 */
class SalesOrderFunnelBoard
{
	const VERSION = 'v1';
	const BOARD_KEY = 'crm_sales_order_funnel';

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
		$funnel = static::buildSalesFunnel();
		$funnel->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($funnel);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$salesFunnelGridByManager = static::buildManagerEfficiencyGrid();
		$salesFunnelGridByManager->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($salesFunnelGridByManager);

		$board->addRows($secondRow);

		return $board;
	}


	protected static function buildSalesFunnel()
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
			ColumnFunnel::CONVERSION_CALCULATE_MODE
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


	/**
	 * @return Widget
	 */
	protected static function buildManagerEfficiencyGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(FunnelGrid::VIEW_KEY);
		$widget->setCategoryKey('sale');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_TITLE'
			)
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'calculateMode',
			ColumnFunnel::CONVERSION_CALCULATE_MODE
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'amountFieldTitle',
			Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_AMOUNT_TITLE')
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'groupingColumnTitle',
			Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_GROUPING_COLUMN_TITLE')
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$orderCount = new Report();
		$orderCount->setGId(Util::generateUserUniqueId());
		$orderCount->setReportClassName(ResponsibleGrid::getClassName());
		$orderCount->setWidget($widget);
		$orderCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_ORDER_COUNT_TITLE'
			)
		);
		$orderCount->getReportHandler(true)->updateFormElementValue('calculate', ResponsibleGrid::WHAT_WILL_CALCULATE_ORDER_COUNT);
		$orderCount->addConfigurations($orderCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($orderCount);

		$orderLoseCount = new Report();
		$orderLoseCount->setGId(Util::generateUserUniqueId());
		$orderLoseCount->setReportClassName(ResponsibleGrid::getClassName());
		$orderLoseCount->setWidget($widget);
		$orderLoseCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_ORDER_LOSES_COUNT_TITLE'
			)
		);
		$orderLoseCount->getReportHandler(true)->updateFormElementValue('calculate', ResponsibleGrid::WHAT_WILL_CALCULATE_ORDER_LOSES_COUNT);
		$orderLoseCount->addConfigurations($orderLoseCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($orderLoseCount);


		$orderSum = new Report();
		$orderSum->setGId(Util::generateUserUniqueId());
		$orderSum->setReportClassName(ResponsibleGrid::getClassName());
		$orderSum->setWidget($widget);
		$orderSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_ORDER_SUM_TITLE'
			)
		);
		$orderSum->getReportHandler(true)->updateFormElementValue('calculate', ResponsibleGrid::WHAT_WILL_CALCULATE_ORDER_SUM);
		$orderSum->addConfigurations($orderSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($orderSum);

		$orderSum = new Report();
		$orderSum->setGId(Util::generateUserUniqueId());
		$orderSum->setReportClassName(ResponsibleGrid::getClassName());
		$orderSum->setWidget($widget);
		$orderSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_WON_ORDER_COUNT_TITLE'
			)
		);
		$orderSum->getReportHandler(true)->updateFormElementValue('calculate', ResponsibleGrid::WHAT_WILL_CALCULATE_ORDER_WON_COUNT);
		$orderSum->addConfigurations($orderSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($orderSum);

		$orderSum = new Report();
		$orderSum->setGId(Util::generateUserUniqueId());
		$orderSum->setReportClassName(ResponsibleGrid::getClassName());
		$orderSum->setWidget($widget);
		$orderSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_WON_ORDER_SUM_TITLE'
			)
		);
		$orderSum->getReportHandler(true)->updateFormElementValue('calculate', ResponsibleGrid::WHAT_WILL_CALCULATE_ORDER_WON_SUM);
		$orderSum->addConfigurations($orderSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($orderSum);

		$conversion = new Report();
		$conversion->setGId(Util::generateUserUniqueId());
		$conversion->setReportClassName(ResponsibleGrid::getClassName());
		$conversion->setWidget($widget);
		$conversion->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_CONVERSION_TITLE'
			)
		);
		$conversion->getReportHandler(true)->updateFormElementValue('color', '#4fc3f7');
		$conversion->getReportHandler(true)->updateFormElementValue('calculate', ResponsibleGrid::WHAT_WILL_CALCULATE_ORDER_CONVERSION);
		$conversion->addConfigurations($conversion->getReportHandler(true)->getConfigurations());
		$widget->addReports($conversion);

		return $widget;
	}
}