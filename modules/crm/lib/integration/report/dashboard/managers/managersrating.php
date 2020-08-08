<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Managers;

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
 * @package Bitrix\Crm\Integration\Report\Dashboard\Managers
 */
class ManagersRating
{
	const VERSION = '4';
	const BOARD_KEY = 'crm_managers_rating';

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
		$graph = self::buildManagersRatingGraph();
		$graph->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($graph);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$grid = self::buildManagersRatingGrid();
		$grid->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($grid);
		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildManagersRatingGraph()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\Managers\ManagersRatingGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_MANAGER_WON_DEALS_RATING'));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$report = new Report();
		$report->setGId(Util::generateUserUniqueId());
		$report->setReportClassName(Handler\Managers\RatingGraph::class);
		$report->setWidget($widget);
		$report->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage("CRM_REPORT_MANAGER_WON_DEALS_AMOUNT")
		);
		$report->getReportHandler(true)->updateFormElementValue("color", "#64b1e2");
		$report->addConfigurations($report->getReportHandler(true)->getConfigurations());
		$widget->addReports($report);

		return $widget;
	}

	private static function buildManagersRatingGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\Managers\ManagersRatingGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage('CRM_REPORT_MANAGER_SALES_CONTRIBUTION'));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$report = new Report();
		$report->setGId(Util::generateUserUniqueId());
		$report->setReportClassName(Handler\Managers\RatingGrid::getClassName());
		$report->setWidget($widget);
		$report->addConfigurations($report->getReportHandler(true)->getConfigurations());
		$widget->addReports($report);

		return $widget;
	}
}