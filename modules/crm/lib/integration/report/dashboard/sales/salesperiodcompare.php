<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\Integration\Report\Handler\SalesPeriodCompare\GraphCurrent;
use Bitrix\Crm\Integration\Report\Handler\SalesPeriodCompare\GraphPrevious;
use Bitrix\Crm\Integration\Report\View\ComparePeriods;
use Bitrix\Crm\Integration\Report\View\ComparePeriodsGrid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
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
	const VERSION = 'v19';
	const BOARD_KEY = 'crm_period_compare';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		Extension::load(["crm.report.salescompareperiods"]);

		return self::buildPeriodCompareDefaultBoard();
	}

	/**
	 * @return Dashboard
	 */
	private static function buildPeriodCompareDefaultBoard()
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
		$firstDealWonSum->setReportClassName(GraphCurrent::getClassName());
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_CURRENT_TIME_PERIOD_TITLE')
		);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#64b1e2');
		$firstDealWonSum->addConfigurations($firstDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($firstDealWonSum);

		$secondDealWonSum = new Report();
		$secondDealWonSum->setGId(Util::generateUserUniqueId());
		$secondDealWonSum->setReportClassName(GraphPrevious::getClassName());
		$secondDealWonSum->setWidget($widget);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_PAST_TIME_PERIOD_TITLE')
		);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#fda505');
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
			Loc::getMessage('CRM_REPORT_SALES_PERIOD_COMPARE_SALES_SUM_DYNAMIC_TITLE')
		);
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$amountWonCurrent = new Report();
		$amountWonCurrent->setGId(Util::generateUserUniqueId());
		$amountWonCurrent->setReportClassName(GraphCurrent::getClassName());
		$amountWonCurrent->setWidget($widget);
		$amountWonCurrent->addConfigurations($amountWonCurrent->getReportHandler(true)->getConfigurations());
		$widget->addReports($amountWonCurrent);

		$amountWonPrev = new Report();
		$amountWonPrev->setGId(Util::generateUserUniqueId());
		$amountWonPrev->setReportClassName(GraphPrevious::getClassName());
		$amountWonPrev->setWidget($widget);
		$amountWonPrev->addConfigurations($amountWonPrev->getReportHandler(true)->getConfigurations());
		$widget->addReports($amountWonPrev);

		return $widget;
	}
}