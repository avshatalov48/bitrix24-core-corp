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
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\ColumnLogarithmic;

/**
 * Class SalesDynamic
 * @package Bitrix\Crm\Integration\Report\Dashboard\Sales
 */
class FinancialRating
{
	const VERSION = '5';
	const BOARD_KEY = 'crm_financial_rating_2';

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
		$linearGraph = self::buildFinancialRatingGraph();
		$linearGraph->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($linearGraph);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$grid = self::buildFinancialRatingGrid();
		$grid->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($grid);
		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildFinancialRatingGraph()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\Customers\FinancialRatingGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label', Loc::getMessage("CRM_REPORT_FIN_RATING_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$firstDealWonSum = new Report();
		$firstDealWonSum->setGId(Util::generateUserUniqueId());
		$firstDealWonSum->setReportClassName(Handler\Customers\FinancialRatingGraph::class);
		$firstDealWonSum->setWidget($widget);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage("CRM_REPORT_FIN_RATING_LABEL")
		);
		$firstDealWonSum->getReportHandler(true)->updateFormElementValue("color", "#64b1e2");
		$firstDealWonSum->addConfigurations($firstDealWonSum->getReportHandler(true)->getConfigurations());
		$widget->addReports($firstDealWonSum);

		return $widget;
	}

	private static function buildFinancialRatingGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\Customers\FinancialRatingGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage("CRM_REPORT_FIN_RATING_GRID_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$sumReport = new Report();
		$sumReport->setGId(Util::generateUserUniqueId());
		$sumReport->setReportClassName(Handler\Customers\FinancialRatingGrid::getClassName());
		$sumReport->setWidget($widget);
		$sumReport->addConfigurations($sumReport->getReportHandler(true)->getConfigurations());
		$widget->addReports($sumReport);

		return $widget;
	}
}