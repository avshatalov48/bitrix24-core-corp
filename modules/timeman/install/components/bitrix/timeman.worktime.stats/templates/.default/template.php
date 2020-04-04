<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load("ui.buttons");
Extension::load("ui.buttons.icons");
Extension::load("ui.hint");
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
if (isset($arResult['SCHEDULE']['NAME']) && $arResult['SCHEDULE']['NAME'])
{
	$APPLICATION->setTitle(htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_SCHEDULE_STATS_TITLE', ['#SCHEDULE_NAME#' => $arResult['SCHEDULE']['NAME']])));
}
else
{
	$APPLICATION->setTitle(htmlspecialcharsbx(Loc::getMessage('TM_WORKTIME_STATS_TITLE')));
}

$APPLICATION->includeComponent('bitrix:timeman.worktime.grid', '', [
	'IS_SLIDER' => $arResult['isSlider'],
	'SHOW_ADD_SCHEDULE_BTN' => $arResult['SHOW_ADD_SCHEDULE_BTN'],
	'GRID_ID' => $arResult['GRID_ID'],
], $component);