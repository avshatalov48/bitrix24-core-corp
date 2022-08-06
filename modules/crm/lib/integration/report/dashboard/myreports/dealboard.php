<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\MyReports;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Integration\Report\View\MyReports\DealReport;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Handler\EmptyReport;
use Bitrix\Report\VisualConstructor\Helper\Util;

Loc::loadMessages(__FILE__);

class DealBoard
{
	const VERSION = '3';
	const BOARD_KEY = 'crm-vc-myreports-deal';
	const CATEGORY_OPTION = "crm.myreports.deal.categoryId";

	public static function getPanelGuid()
	{
		return static::getCurrentCategory() >= 0 ? 'deal_category_widget' : 'deal_widget';
	}

	/**
	 * Returns current category for deal widgets.
	 *
	 * @return int
	 */
	public static function getCurrentCategory()
	{
		return (int)\CUserOptions::GetOption("crm", static::CATEGORY_OPTION, 0);
	}

	public static function getCurrentCategoryName()
	{
		$currentCategory = static::getCurrentCategory();
		return $currentCategory >= 0 ? DealCategory::getName(static::getCurrentCategory()) : Loc::getMessage("CRM_REPORT_MY_REPORTS_DEAL_ALL_DEALS");
	}

	/**
	 * Sets current category for deal widgets
	 *
	 * @param int $categoryId Id of the category.
	 * @return bool
	 */
	public static function setCurrentCategory($categoryId)
	{
		return \CUserOptions::SetOption("crm", static::CATEGORY_OPTION, (string)$categoryId);
	}

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
		$widget = static::buildWidget();
		$widget->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($widget);
		$board->addRows($firstRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildWidget()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(DealReport::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widgetHandler = $widget->getWidgetHandler(true);
		$widgetHandler->getConfiguration('color')->setValue('transparent');
		$widget->addConfigurations($widgetHandler->getConfigurations());

		return $widget;
	}
}