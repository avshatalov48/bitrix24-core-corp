<?php

namespace Bitrix\Crm\Integration\Report\Dashboard\Ad;


use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor as VC;
use Bitrix\Crm\Integration\Report\View;

Loc::loadMessages(__FILE__);

/**
 * Class AdPayback
 * @package Bitrix\Crm\Integration\Report\Dashboard\Ad
 */
class AdPayback
{
	const VERSION = 'v2';
	const BOARD_KEY = 'crm-ad-payback';

	/**
	 * @return VC\Entity\Dashboard
	 */
	public static function get()
	{
		$board = new VC\Entity\Dashboard();
		$board->setVersion(self::VERSION);
		$board->setBoardKey(self::BOARD_KEY);
		$board->setGId(VC\Helper\Util::generateUserUniqueId());
		$board->setUserId(0);


		$firstRow = VC\Entity\DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$funnel = self::buildFunnelWidget();
		$funnel->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($funnel);
		$board->addRows($firstRow);

		$secondRow = VC\Entity\DashboardRow::factoryWithHorizontalCells(1);
		$secondRow->setWeight(2);
		$salesFunnelGridByManager = self::buildGridWidget();
		$salesFunnelGridByManager->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($salesFunnelGridByManager);
		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return VC\Entity\Widget
	 */
	private static function buildFunnelWidget()
	{
		$widget = new VC\Entity\Widget();
		$widget->setGId(VC\Helper\Util::generateUserUniqueId());
		$widget->setWidgetClass(VC\Handler\BaseWidget::getClassName());
		$widget->setViewKey(View\AdsFunnel::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage("CRM_INTEGRATION_REPORT_AD_PAYBACK_FUNNEL_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		return $widget;
	}

	/**
	 * @return VC\Entity\Widget
	 */
	private static function buildGridWidget()
	{
		$widget = new VC\Entity\Widget();
		$widget->setGId(VC\Helper\Util::generateUserUniqueId());
		$widget->setWidgetClass(VC\Handler\BaseWidget::getClassName());
		$widget->setViewKey(View\AdsGrid::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::BOARD_KEY);

		$widget->getWidgetHandler(true)->updateFormElementValue('label',  Loc::getMessage("CRM_INTEGRATION_REPORT_AD_PAYBACK_GRID_TITLE"));
		$widget->addConfigurations($widget->getWidgetHandler(true)->getConfigurations());

		return $widget;
	}
}