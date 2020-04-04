<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Sales;

use Bitrix\Crm\Integration\Report\View\SalesPlan;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;

class SalesPlanBoard
{
	const VERSION = 'v1';
	const BOARD_KEY = 'crm_sales_plan';

	public static function get()
	{
		$board = new Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(self::BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$salesTarget = self::buildSalesPlan();
		$salesTarget->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($salesTarget);

		$board->addRows($firstRow);

		return $board;
	}

	private static function buildSalesPlan()
	{
		$salesTarget = new Widget();
		$salesTarget->setGId(Util::generateUserUniqueId());
		$salesTarget->setWidgetClass(BaseWidget::getClassName());
		$salesTarget->setViewKey(SalesPlan::VIEW_KEY);
		$salesTarget->setCategoryKey('crm');
		$salesTarget->setBoardId(self::BOARD_KEY);

		$salesTarget->getWidgetHandler(true)->updateFormElementValue(
			'label',
			Loc::getMessage(
				'CRM_REPORT_DASHBOARD_SALES_TARGET_WIDGET_TITLE'
			)
		);
		$salesTarget->addConfigurations($salesTarget->getWidgetHandler(true)->getConfigurations());

		return $salesTarget;
	}

}