<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\Integration\Report\Handler\Lead;
use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
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

	const VERSION = 'v7';
	const BOARD_KEY = 'crm_sales_funnel';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		return self::buildSalesFunnelDefaultBoard();
	}

	/**
	 * @return Dashboard
	 */
	private static function buildSalesFunnelDefaultBoard()
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(static::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$funnel = self::buildSalesFunnel();
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

	/**
	 * @return Widget
	 */
	private static function buildSalesFunnel()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(ColumnFunnel::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_TITLE'
			)
		);

		$widget->getWidgetHandler()->updateFormElementValue(
			'calculateMode',
			ColumnFunnel::CONVERSION_CALCULATE_MODE
		);

		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$leadCount = new Report();
		$leadCount->setGId(Util::generateUserUniqueId());
		$leadCount->setReportClassName(Lead::getClassName());
		$leadCount->setWidget($widget);
		$leadCount->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_LEAD_COUNT_TITLE'
			)
		);
		$leadCount->getReportHandler()->updateFormElementValue('groupingBy', Lead::GROUPING_BY_STATE);
		$leadCount->getReportHandler()->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT);
		$leadCount->addConfigurations($leadCount->getReportHandler()->getConfigurations());
		$widget->addReports($leadCount);

		$dealCount = new Report();
		$dealCount->setGId(Util::generateUserUniqueId());
		$dealCount->setReportClassName(Deal::getClassName());
		$dealCount->setWidget($widget);
		$dealCount->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_DEAL_METRIC_TITLE'
			)
		);
		$dealCount->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_STAGE);
		$dealCount->getReportHandler()->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_COUNT_AND_SUM
		);
		$dealCount->addConfigurations($dealCount->getReportHandler()->getConfigurations());
		$widget->addReports($dealCount);

		$conversion = new Report();
		$conversion->setGId(Util::generateUserUniqueId());
		$conversion->setReportClassName(Deal::getClassName());
		$conversion->setWidget($widget);
		$conversion->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_CONVERSION_TITLE'
			)
		);
		$conversion->getReportHandler()->updateFormElementValue('color', '#4fc3f7');
		$conversion->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_CONVERSION);
		$conversion->addConfigurations($conversion->getReportHandler()->getConfigurations());
		$widget->addReports($conversion);

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
		$widget->setViewKey(Grid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_TITLE'
			)
		);

		$widget->getWidgetHandler()->updateFormElementValue(
			'amountFieldTitle',
			Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_AMOUNT_TITLE')
		);

		$widget->getWidgetHandler()->updateFormElementValue(
			'groupingColumnTitle',
			Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_GROUPING_COLUMN_TITLE')
		);

		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$leadCount = new Report();
		$leadCount->setGId(Util::generateUserUniqueId());
		$leadCount->setReportClassName(Lead::getClassName());
		$leadCount->setWidget($widget);
		$leadCount->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_LEAD_COUNT_TITLE'
			)
		);
		$leadCount->getReportHandler()->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$leadCount->getReportHandler()->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_COUNT);
		$leadCount->addConfigurations($leadCount->getReportHandler()->getConfigurations());
		$widget->addReports($leadCount);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_DEAL_COUNT_TITLE'
			)
		);
		$dealSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_COUNT);
		$dealSum->addConfigurations($dealSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealSum);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_DEAL_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealSum);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_WON_DEAL_COUNT_TITLE'
			)
		);
		$dealSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_COUNT);
		$dealSum->addConfigurations($dealSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealSum);

		$dealSum = new Report();
		$dealSum->setGId(Util::generateUserUniqueId());
		$dealSum->setReportClassName(Deal::getClassName());
		$dealSum->setWidget($widget);
		$dealSum->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_WON_DEAL_SUM_TITLE'
			)
		);
		$dealSum->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$dealSum->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_WON_SUM);
		$dealSum->addConfigurations($dealSum->getReportHandler()->getConfigurations());
		$widget->addReports($dealSum);

		$conversion = new Report();
		$conversion->setGId(Util::generateUserUniqueId());
		$conversion->setReportClassName(Deal::getClassName());
		$conversion->setWidget($widget);
		$conversion->getReportHandler()->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_MANAGER_EFFICIENCY_GRID_CONVERSION_TITLE'
			)
		);
		$conversion->getReportHandler()->updateFormElementValue('color', '#4fc3f7');
		$conversion->getReportHandler()->updateFormElementValue('groupingBy', Deal::GROUPING_BY_RESPONSIBLE);
		$conversion->getReportHandler()->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_DEAL_CONVERSION);
		$conversion->addConfigurations($conversion->getReportHandler()->getConfigurations());
		$widget->addReports($conversion);

		return $widget;
	}

}