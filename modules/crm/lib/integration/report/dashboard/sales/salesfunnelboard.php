<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\Integration\Report\Handler\Lead;
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

/**
 * Class SalesFunnelBoard
 * @package Bitrix\Crm\Integration\Report\Dashboard
 */
class SalesFunnelBoard
{

	const VERSION = 'v19';
	const BOARD_KEY = 'crm_sales_funnel';
	const SHOW_LEADS_OPTION = 'analytics_show_leads_in_sales';

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

	/**
	 * @return Widget
	 */
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

		$leadCount = new Report();
		$leadCount->setGId(Util::generateUserUniqueId());
		$leadCount->setReportClassName(Lead::getClassName());
		$leadCount->setWidget($widget);
		$leadCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_LEAD_COUNT_TITLE'
			)
		);
		$leadCount->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_STATE);
		$leadCount->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL);
		$leadCount->addConfigurations($leadCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($leadCount);

		$dealCount = new Report();
		$dealCount->setGId(Util::generateUserUniqueId());
		$dealCount->setReportClassName(Deal::getClassName());
		$dealCount->setWidget($widget);
		$dealCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_DEAL_METRIC_TITLE'
			)
		);
		$dealCount->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_STAGE);
		$dealCount->getReportHandler(true)->updateFormElementValue('disableSuccessStages', true);
		$dealCount->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL
		);
		$dealCount->addConfigurations($dealCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealCount);

		$successDealCount = new Report();
		$successDealCount->setGId(Util::generateUserUniqueId());
		$successDealCount->setReportClassName(Deal::getClassName());
		$successDealCount->setWidget($widget);
		$successDealCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_SUCCESS_DEAL_TITLE'
			)
		);
		$successDealCount->getReportHandler(true)->updateFormElementValue('color', '#4fc3f7');
		$successDealCount->getReportHandler(true)->updateFormElementValue('shortMode', true);
		$successDealCount->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_STAGE);
		$successDealCount->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL);
		$successDealCount->addConfigurations($successDealCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($successDealCount);

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
		$widget->setCategoryKey('crm');
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

		$leadCount = new Report();
		$leadCount->setGId(Util::generateUserUniqueId());
		$leadCount->setReportClassName(Lead::getClassName());
		$leadCount->setWidget($widget);
		$leadCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_LEAD_COUNT_TITLE'
			)
		);
		$leadCount->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$leadCount->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_COUNT);
		$leadCount->addConfigurations($leadCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($leadCount);

		$dealCount = new Report();
		$dealCount->setGId(Util::generateUserUniqueId());
		$dealCount->setReportClassName(Deal::getClassName());
		$dealCount->setWidget($widget);
		$dealCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_DEAL_COUNT_TITLE'
			)
		);
		$dealCount->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealCount->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_COUNT);
		$dealCount->addConfigurations($dealCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealCount);

		$dealLoseCount = new Report();
		$dealLoseCount->setGId(Util::generateUserUniqueId());
		$dealLoseCount->setReportClassName(Deal::getClassName());
		$dealLoseCount->setWidget($widget);
		$dealLoseCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_DEAL_LOSES_COUNT_TITLE'
			)
		);
		$dealLoseCount->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealLoseCount->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_LOSES_COUNT);
		$dealLoseCount->addConfigurations($dealLoseCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealLoseCount);


		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_DEAL_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealSum);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_WON_DEAL_COUNT_TITLE'
			)
		);
		$dealSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_COUNT);
		$dealSum->addConfigurations($dealSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealSum);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_WON_DEAL_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealSum);

		$conversion = new Report();
		$conversion->setGId(Util::generateUserUniqueId());
		$conversion->setReportClassName(Deal::getClassName());
		$conversion->setWidget($widget);
		$conversion->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_CONVERSION_TITLE'
			)
		);
		$conversion->getReportHandler(true)->updateFormElementValue('color', '#4fc3f7');
		$conversion->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$conversion->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_CONVERSION);
		$conversion->addConfigurations($conversion->getReportHandler(true)->getConfigurations());
		$widget->addReports($conversion);

		return $widget;
	}

}