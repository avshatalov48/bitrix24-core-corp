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

class SalesFunnelByStageHistory extends SalesFunnelBoard
{
	const VERSION = 'v18';
	const BOARD_KEY = 'crm_sales_funnel_by_stage_history';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(static::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$funnel = self::buildSalesFunnelByStageHistory();
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


	private static function buildSalesFunnelByStageHistory()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(ColumnFunnel::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_TITLE'
			)
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'calculateMode',
			ColumnFunnel::CLASSIC_CALCULATE_MODE
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$leadCount = new Report();
		$leadCount->setGId(Util::generateUserUniqueId());
		$leadCount->setReportClassName(Lead::getClassName());
		$leadCount->setWidget($widget);
		$leadCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_LEAD_COUNT_TITLE'
			)
		);
		$leadCount->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_STATE);
		$leadCount->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL);
		$leadCount->addConfigurations($leadCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($leadCount);

		$dealCount = new Report();
		$dealCount->setGId(Util::generateUserUniqueId());
		$dealCount->setReportClassName(Deal::getClassName());
		$dealCount->setWidget($widget);
		$dealCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_GOOD_DEAL_METRIC_TITLE'
			)
		);
		$dealCount->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_STAGE);
		$dealCount->getReportHandler(true)->updateFormElementValue('disableSuccessStages', true);
		$dealCount->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Deal::WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL
		);
		$dealCount->addConfigurations($dealCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($dealCount);

		$successDealCount = new Report();
		$successDealCount->setGId(Util::generateUserUniqueId());
		$successDealCount->setReportClassName(Deal::getClassName());
		$successDealCount->setWidget($widget);
		$successDealCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_FUNNEL_BOARD_SALES_FUNNEL_SUCCESS_DEAL_TITLE'
			)
		);
		$successDealCount->getReportHandler(true)->updateFormElementValue('color', '#4fc3f7');
		$successDealCount->getReportHandler(true)->updateFormElementValue('shortMode', true);
		$successDealCount->getReportHandler(true)->updateFormElementValue('groupingBy', Deal::GROUPING_BY_STAGE);
		$successDealCount->getReportHandler(true)->updateFormElementValue('calculate', Deal::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL);
		$successDealCount->addConfigurations($successDealCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($successDealCount);

		return $widget;
	}

	public static function buildManagerEfficiencyGrid()
	{
		$widget = parent::buildManagerEfficiencyGrid();

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'calculateMode',
			ColumnFunnel::CLASSIC_CALCULATE_MODE
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		return $widget;
	}
}