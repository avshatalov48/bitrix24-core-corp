<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Customers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Report\View;
use \Bitrix\Crm\Integration\Report\Handler;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;

/**
 * Class SalesDynamic
 * @package Bitrix\Crm\Integration\Report\Dashboard\Sales
 */
class RegularCustomers
{
	const VERSION = '5';
	const BOARD_KEY = 'crm_regular_customers';

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
		$linearGraph = self::buildRegularCustomersGraph();
		$linearGraph->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($linearGraph);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$grid = self::buildRegularCustomersGrid();
		$grid->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($grid);
		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildRegularCustomersGraph()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\Customers\RegularCustomersGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label', Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(Handler\Customers\RegularCustomers::class);
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_CUSTOMERS_COUNT")
		);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue("color", "#64b1e2");
		$firstDealWonSum->addConfigurations($firstDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($firstDealWonSum);

		return $widget;
	}

	private static function buildRegularCustomersGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\Customers\RegularCustomersGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_RATING_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$sumReport = new Report();
		$sumReport->setGId(Util::generateUserUniqueId());
		$sumReport->setReportClassName(Handler\Customers\RegularCustomersGrid::getClassName());
		$sumReport->setWidget($widget);
		$sumReport->addConfigurations($sumReport->getReportHandler(true)->getConfigurations());
		$widget->addReports($sumReport);

		return $widget;
	}

}