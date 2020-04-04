<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\Handler\Deal;
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
	const VERSION = 'v0';
	const BOARD_KEY = 'crm_period_compare';

	/**
	 *
	 * @return Dashboard
	 */
	public static function get()
	{
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
		$widget->setViewKey(LinearGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_TITLE'
			)
		);
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(Deal::getClassName());
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_CURRENT_YEAR_TITLE'
			)
		);
		$firstDealWonSum->getReportHandler()->updateFormElementValue('color', '#64b1e2');
		$firstDealWonSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$firstDealWonSum->getReportHandler()->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_FIRST_DEAL_WON_SUM
		);
		$firstDealWonSum->addConfigurations($firstDealWonSum->getReportHandler()->getConfigurations());
		$widget->addReports($firstDealWonSum);

		$secondDealWonSum = new Report();
		$secondDealWonSum->setGId(Util::generateUserUniqueId());
		$secondDealWonSum->setReportClassName(Deal::getClassName());
		$secondDealWonSum->setWidget($widget);
		$secondDealWonSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_LINEAR_GRAPH_PAST_YEAR_TITLE'
			)
		);
		$secondDealWonSum->getReportHandler()->updateFormElementValue('color', '#fda505');
		$secondDealWonSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$secondDealWonSum->getReportHandler()->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM
		);
		$secondDealWonSum->addConfigurations($secondDealWonSum->getReportHandler()->getConfigurations());
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
		$widget->setViewKey(Grid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_SUM_DYNAMIC_TITLE'
			)
		);
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_PERIOD_COMPARE_SALES_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$dealSum->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealSum);

		return $widget;
	}
}