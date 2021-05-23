<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\Integration\Report\Handler\SalesDynamics;
use Bitrix\Crm\Integration\Report\View;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;
use Bitrix\Report\VisualConstructor\Views\Component\Grid;

/**
 * Class SalesDynamic
 * @package Bitrix\Crm\Integration\Report\Dashboard\Sales
 */
class SalesDynamic
{
	const VERSION = 'v25';
	const BOARD_KEY = 'crm_sales_dynamic';

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
		$widget->setViewKey(View\SalesDynamicsGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_TITLE'));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(SalesDynamics\PrimaryGraph::class);
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_FIRST_LEAD_TITLE')
		);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#64b1e2');
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$firstDealWonSum->addConfigurations($firstDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($firstDealWonSum);

		$secondDealWonSum = new Report();
		$secondDealWonSum->setGId(Util::generateUserUniqueId());
		$secondDealWonSum->setReportClassName(SalesDynamics\ReturnGraph::getClassName());
		$secondDealWonSum->setWidget($widget);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_REPEAT_LEAD_TITLE')
		);
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue('color', '#fda505');
		$secondDealWonSum->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_DATE);
		$secondDealWonSum->addConfigurations($secondDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($secondDealWonSum);

		return $widget;
	}

	private static function buildManagerSalesDynamicGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\SalesDynamicsGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage("CRM_REPORT_SALES_DYNAMIC_GRID_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$sumReport = new Report();
		$sumReport->setGId(Util::generateUserUniqueId());
		$sumReport->setReportClassName(SalesDynamics\WonLostAmount::getClassName());
		$sumReport->setWidget($widget);
		$sumReport->addConfigurations($sumReport->getReportHandler(true)->getConfigurations());
		$widget->addReports($sumReport);

		$conversionReport = new Report();
		$conversionReport->setGId(Util::generateUserUniqueId());
		$conversionReport->setReportClassName(SalesDynamics\Conversion::getClassName());
		$conversionReport->setWidget($widget);
		$conversionReport->addConfigurations($conversionReport->getReportHandler(true)->getConfigurations());
		$widget->addReports($conversionReport);

		$previousPeriodConversionReport = new Report();
		$previousPeriodConversionReport->setGId(Util::generateUserUniqueId());
		$previousPeriodConversionReport->setReportClassName(SalesDynamics\WonLostPrevious::getClassName());
		$previousPeriodConversionReport->setWidget($widget);
		$previousPeriodConversionReport->addConfigurations($previousPeriodConversionReport->getReportHandler(true)->getConfigurations());
		$widget->addReports($previousPeriodConversionReport);

		return $widget;
	}
}