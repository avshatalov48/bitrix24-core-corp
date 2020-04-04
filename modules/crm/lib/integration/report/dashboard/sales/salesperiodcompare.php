<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\Integration\Report\View\ComparePeriods;
use Bitrix\Crm\Integration\Report\View\ComparePeriodsGrid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;
use Bitrix\Report\VisualConstructor\Views\Component\Grid;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\LinearGraph;

/**
 * Class SalesPeriodCompare
 * @package Bitrix\Crm\Integration\Report\Dashboard\Sales
 */
class SalesPeriodCompare
{
	const VERSION = 'v17';
	const BOARD_KEY = 'crm_period_compare';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(self::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$periodCompareGraph = self::buildPeriodCompareLinearGraph();
		$periodCompareGraph->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($periodCompareGraph);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$salesPeriodCompareGridByDate = self::buildPeriodCompareGridByDate();
		$salesPeriodCompareGridByDate->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($salesPeriodCompareGridByDate);

		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildPeriodCompareLinearGraph()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(ComparePeriods::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_TITLE'
			)
		);
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(Deal::getClassName());
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_CURRENT_TIME_PERIOD_TITLE'
			)
		);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#64b1e2');
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM
		);
		$firstDealWonSum->addConfigurations($firstDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($firstDealWonSum);

		$secondDealWonSum = new Report();
		$secondDealWonSum->setGId(Util::generateUserUniqueId());
		$secondDealWonSum->setReportClassName(Deal::getClassName());
		$secondDealWonSum->setWidget($widget);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_PAST_TIME_PERIOD_TITLE'
			)
		);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#fda505');
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue('pastPeriod', true);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM
		);
		$secondDealWonSum->addConfigurations($secondDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($secondDealWonSum);

		return $widget;
	}

	/**
	 * @return Widget
	 */
	private static function buildPeriodCompareGridByDate()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(ComparePeriodsGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_SUM_DYNAMIC_TITLE'
			)
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'groupingColumnTitle',
			Loc::getMessage('CRM_REPORT_SALES_PERIOD_COMPARE_GRID_GROUPING_COLUMN_TITLE')
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'amountFieldTitle',
			Loc::getMessage('CRM_REPORT_SALES_PERIOD_COMPARE_GRID_AMOUNT_TITLE')
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$dealSum->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler(true)->getConfigurations());
		//$widget->addReports($dealSum);



		$dealWonSum = new Report();
		$dealWonSum->setGId(Util::generateUserUniqueId());
		$dealWonSum->setReportClassName(Deal::getClassName());
		$dealWonSum->setWidget($widget);
		$dealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_WON_SUM_TITLE'
			)
		);
		$dealWonSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$dealWonSum->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM);
		$dealWonSum->addConfigurations($dealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealWonSum);




		$pastPeriodDealSum = new Report();
		$pastPeriodDealSum->setGId(Util::generateUserUniqueId());
		$pastPeriodDealSum->setReportClassName(Deal::getClassName());
		$pastPeriodDealSum->setWidget($widget);
		$pastPeriodDealSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_SUM_TITLE_PAST_PERIOD'
			)
		);
		$pastPeriodDealSum->getReportHandler(true)->updateFormElementValue('color', '#fda505');
		$pastPeriodDealSum->getReportHandler(true)->updateFormElementValue('pastPeriod', true);
		$pastPeriodDealSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$pastPeriodDealSum->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_SUM
		);
		$pastPeriodDealSum->addConfigurations($pastPeriodDealSum->getReportHandler(true)->getConfigurations());
		//$widget->addReports($pastPeriodDealSum);


		$pastPeriodDealWonSum = new Report();
		$pastPeriodDealWonSum->setGId(Util::generateUserUniqueId());
		$pastPeriodDealWonSum->setReportClassName(Deal::getClassName());
		$pastPeriodDealWonSum->setWidget($widget);
		$pastPeriodDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_WON_SUM_TITLE_PAST_PERIOD'
			)
		);
		$pastPeriodDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#fda505');
		$pastPeriodDealWonSum->getReportHandler(true)->updateFormElementValue('pastPeriod', true);
		$pastPeriodDealWonSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$pastPeriodDealWonSum->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM
		);
		$pastPeriodDealWonSum->addConfigurations($pastPeriodDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($pastPeriodDealWonSum);

		return $widget;
	}
}