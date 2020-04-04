<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic;

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
 * Class CommonLead
 * @package Bitrix\Crm\Integration\Report\Dashboard
 */
class CommonLead
{
	const VERSION = 'v13';
	const BOARD_KEY = 'crm_common_lead_analytics';

	/**
	 * @return Dashboard
	 */
	public static function get()
	{
		$board = new Dashboard();
		$board->setVersion(static::VERSION);
		$board->setBoardKey(static::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);

		$leadFunnel = static::buildLeadColumnFunnel();
		$leadFunnel->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($leadFunnel);

		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);

		$grid = static::buildLeadByResponsibleGrid();
		$grid->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($grid);

		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	protected static function buildLeadColumnFunnel()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(ColumnFunnel::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_COLUMN_FUNNEL_TITLE')
		);
		$widget->getWidgetHandler(true)->updateFormElementValue('shortMode', true);
		$widget->getWidgetHandler(true)->updateFormElementValue('calculateMode', ColumnFunnel::CLASSIC_CALCULATE_MODE);
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$leadFunnel = new Report();
		$leadFunnel->setGId(Util::generateUserUniqueId());
		$leadFunnel->setReportClassName(Lead::getClassName());
		$leadFunnel->setWidget($widget);
		$leadFunnel->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_COLUMN_FUNNEL_LEAD_COUNT_TITLE_1'
			)
		);
		$leadFunnel->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_STATE);
		$leadFunnel->getReportHandler(true)->updateFormElementValue('disableSuccessStates', true);
		$leadFunnel->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_DATA_FOR_FUNNEL);
		$leadFunnel->addConfigurations($leadFunnel->getReportHandler(true)->getConfigurations());
		$widget->addReports($leadFunnel);


		$successLeadData = new Report();
		$successLeadData->setGId(Util::generateUserUniqueId());
		$successLeadData->setReportClassName(Lead::getClassName());
		$successLeadData->setWidget($widget);
		$successLeadData->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_COLUMN_FUNNEL_SUCCESS_LEAD_TITLE_1'
			)
		);
		$successLeadData->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_STATE);
		$successLeadData->getReportHandler(true)->updateFormElementValue('shortMode', true);
		$successLeadData->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_SUCCESS_LEAD_DATA_FOR_FUNNEL);
		$successLeadData->addConfigurations($successLeadData->getReportHandler(true)->getConfigurations());
		$widget->addReports($successLeadData);

		return $widget;
	}

	/**
	 * @return Widget
	 */
	protected static function buildLeadByResponsibleGrid()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(Grid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(static::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_MANAGER_GRID_TITLE')
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'amountFieldTitle',
			Loc::getMessage('CRM_REPORT_SALES_MANAGER_GRID_AMOUNT_TITLE')
		);

		$widget->getWidgetHandler(true)->updateFormElementValue(
			'groupingColumnTitle',
			Loc::getMessage('CRM_REPORT_SALES_MANAGER_GRID_GROUPING_COLUMN_TITLE')
		);

		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		$leadCount = new Report();
		$leadCount->setGId(Util::generateUserUniqueId());
		$leadCount->setReportClassName(Lead::getClassName());
		$leadCount->setWidget($widget);
		$leadCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_MANAGER_GRID_ACTIVE_LEAD_COUNT_TITLE'
			)
		);
		$leadCount->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$leadCount->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Lead::WHAT_WILL_CALCULATE_LEAD_COUNT
		);
		$leadCount->addConfigurations($leadCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($leadCount);

		$successLeadCount = new Report();
		$successLeadCount->setGId(Util::generateUserUniqueId());
		$successLeadCount->setReportClassName(Lead::getClassName());
		$successLeadCount->setWidget($widget);
		$successLeadCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_MANAGER_GRID_CONVERTED_LEAD_COUNT_TITLE'
			)
		);
		$successLeadCount->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$successLeadCount->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Lead::WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT
		);
		$successLeadCount->addConfigurations($successLeadCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($successLeadCount);

		$lostLeadCount = new Report();
		$lostLeadCount->setGId(Util::generateUserUniqueId());
		$lostLeadCount->setReportClassName(Lead::getClassName());
		$lostLeadCount->setWidget($widget);
		$lostLeadCount->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_MANAGER_GRID_LOSE_LEAD_COUNT_TITLE_1'
			)
		);
		$lostLeadCount->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$lostLeadCount->getReportHandler(true)->updateFormElementValue(
			'calculate',
			Lead::WHAT_WILL_CALCULATE_LOST_LEAD_COUNT
		);
		$lostLeadCount->addConfigurations($lostLeadCount->getReportHandler(true)->getConfigurations());
		$widget->addReports($lostLeadCount);

		$loses = new Report();
		$loses->setGId(Util::generateUserUniqueId());
		$loses->setReportClassName(Lead::getClassName());
		$loses->setWidget($widget);
		$loses->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage('CRM_REPORT_SALES_MANAGER_GRID_LOSES_TITLE')
		);
		$loses->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$loses->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_LOSES);
		$loses->addConfigurations($loses->getReportHandler(true)->getConfigurations());
		$widget->addReports($loses);

		$conversion = new Report();
		$conversion->setGId(Util::generateUserUniqueId());
		$conversion->setReportClassName(Lead::getClassName());
		$conversion->setWidget($widget);
		$conversion->getReportHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_SALES_MANAGER_GRID_CONVERSION_TITLE'
			)
		);
		$conversion->getReportHandler(true)->updateFormElementValue('groupingBy', Lead::GROUPING_BY_RESPONSIBLE);
		$conversion->getReportHandler(true)->updateFormElementValue('calculate', Lead::WHAT_WILL_CALCULATE_LEAD_CONVERSION);
		$conversion->addConfigurations($conversion->getReportHandler(true)->getConfigurations());
		$widget->addReports($conversion);

		return $widget;
	}
}