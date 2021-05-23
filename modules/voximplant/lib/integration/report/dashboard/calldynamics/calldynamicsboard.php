<?php

namespace Bitrix\Voximplant\Integration\Report\Dashboard\CallDynamics;

use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;
use Bitrix\Voximplant\Integration\Report\View;
use Bitrix\Voximplant\Integration\Report\Handler;

/**
 * Class CallDynamicsBoard
 * @package Bitrix\Voximplant\Integration\Report\Dashboard\GeneralAnalysis
 */
class CallDynamicsBoard
{
	public const VERSION = 'v15';
	public const BOARD_KEY = 'telephony_calls_dynamics';

	/**
	 * @return Dashboard
	 */
	public static function get(): Dashboard
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(static::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$chart = static::buildCallDynamicsGraph();
		$chart->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($chart);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$grid = static::buildCallDynamicsGrid();
		$grid->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($grid);
		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	protected static function buildCallDynamicsGraph(): Widget
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\CallDynamics\CallDynamicsGraph::VIEW_KEY);
		$widget->setCategoryKey('telephony');
		$widget->setBoardId(static::BOARD_KEY);
		$widget->getWidgetHandler(true)
			   ->updateFormElementValue('label', Loc::getMessage('TELEPHONY_REPORT_CALL_DYNAMICS'));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$report = new Report();
		$report->setGId(Util::generateUserUniqueId());
		$report->setReportClassName(Handler\CallDynamics\CallDynamicsGraph::class);
		$report->setWidget($widget);
		$report->addConfigurations($report->getReportHandler(true)->getConfigurations());
		$widget->addReports($report);

		return $widget;
	}

	/**
	 * @return Widget
	 */
	protected static function buildCallDynamicsGrid(): Widget
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(View\CallDynamics\CallDynamicsGrid::VIEW_KEY);
		$widget->setCategoryKey('telephony');
		$widget->setBoardId(static::BOARD_KEY);
		$widget->getWidgetHandler(true)
			   ->updateFormElementValue('label', Loc::getMessage('TELEPHONY_REPORT_CALL_DISTRIBUTION'));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$report = new Report();
		$report->setGId(Util::generateUserUniqueId());
		$report->setReportClassName(Handler\CallDynamics\CallDynamicsGrid::class);
		$report->setWidget($widget);
		$report->addConfigurations($report->getReportHandler(true)->getConfigurations());
		$widget->addReports($report);

		return $widget;
	}
}