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
 * Class SalesDynamic
 * @package Bitrix\Crm\Integration\Report\Dashboard\Sales
 */
class SalesDynamic
{
	const VERSION = 'v0';
	const BOARD_KEY = 'crm_sales_dynamic';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		return self::buildSalesDynamicDefaultBoard();
	}

	/**
	 * @return Dashboard
	 */
	private static function buildSalesDynamicDefaultBoard()
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(self::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$linearGraph = self::buildSalesDynamic();
		$linearGraph->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($linearGraph);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$salesDynamicGridByManager = self::buildManagerSalesDynamicGrid();
		$salesDynamicGridByManager->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($salesDynamicGridByManager);

		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildSalesDynamic()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(LinearGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_TITLE'));
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(Deal::getClassName());
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_DYNAMIC_FIRST_LEAD_TITLE'
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
				'CRM_REPORT_SALES_DYNAMIC_REPEAT_LEAD_TITLE'
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
	private static function buildManagerSalesDynamicGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(Grid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_GRID_TITLE'));
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(Deal::getClassName());
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_DYNAMIC_GRID_FIRST_DEAL_WON_SUM_TITLE'
			)
		);
		$firstDealWonSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
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
				'CRM_REPORT_SALES_DYNAMIC_GRID_REPEAT_DEAL_WON_SUM_TITLE'
			)
		);
		$secondDealWonSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$secondDealWonSum->getReportHandler()->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM
		);
		$secondDealWonSum->addConfigurations($secondDealWonSum->getReportHandler()->getConfigurations());
		$widget->addReports($secondDealWonSum);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_DYNAMIC_GRID_DEAL_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealSum);

		$dealLosesSum = new Report();
		$dealLosesSum->setGId(Util::generateUserUniqueId());
		$dealLosesSum->setReportClassName(Deal::getClassName());
		$dealLosesSum->setWidget($widget);
		$dealLosesSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_DYNAMIC_GRID_LOSES_SUM_TITLE'
			)
		);
		$dealLosesSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealLosesSum->getReportHandler()->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM
		);
		$dealLosesSum->addConfigurations($dealLosesSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealLosesSum);

		$conversion = new Report();
		$conversion->setGId(Util::generateUserUniqueId());
		$conversion->setReportClassName(Deal::getClassName());
		$conversion->setWidget($widget);
		$conversion->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_DYNAMIC_GRID_CONVERSION_TITLE'
			)
		);
		$conversion->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$conversion->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_CONVERSION);
		$conversion->addConfigurations($conversion->getReportHandler()->getConfigurations());
		$widget->addReports($conversion);

		return $widget;
	}
}