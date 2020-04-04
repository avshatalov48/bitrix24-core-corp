<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Timeman\Helper\DateTimeHelper;

$dateHelper = new DateTimeHelper();
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
	'WRAP_CELL_IN_RECORD_LINK' => true,
	'SHOW_GRID_SETTINGS_BTN' => true,
	'SHOW_SCHEDULES_LIST_BTN' => !empty($arResult['schedulesData']),
	'SHOW_ADD_SCHEDULE_BTN' => $arResult['SHOW_ADD_SCHEDULE_BTN'],
	'SHOW_SHIFTPLAN_LIST_BTN' => !empty($arResult['SHIFTED_SCHEDULES']),
	'SHOW_DELETE_SHIFT_PLAN_BTN' => false,
	'SHOW_START_FINISH' => $arResult['SHOW_START_FINISH'],
	'GRID_ID' => $arResult['GRID_ID'],
	'GRID_OPTIONS' => $arResult['gridOptions'] +
					  [
						  'ENABLE_STATS_COLUMNS' => true,
						  'SHOW_USER_ABSENCES' => true,
					  ],
], $component);

?>
<script>
	BX.ready(function ()
	{
		BX.message({
			TM_SCHEDULE_LIST_ACTION_DELETE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_DELETE')) ?>',
			TM_SCHEDULE_LIST_ACTION_EDIT: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_EDIT')) ?>',
			TM_SCHEDULE_LIST_ACTION_READ: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_LIST_ACTION_READ')) ?>',
			TM_SCHEDULE_DELETE_CONFIRM_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM_TITLE')) ?>',
			TM_SCHEDULE_DELETE_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM')) ?>',
			TM_SCHEDULE_DELETE_CONFIRM_NO: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM_NO')) ?>',
			TM_SCHEDULE_DELETE_CONFIRM_YES: '<?= CUtil::JSEscape(Loc::getMessage('TM_SCHEDULE_DELETE_CONFIRM_YES')) ?>'
		});
		new BX.Timeman.Component.Worktime.Stats({
			gridSettings: <?= CUtil::PhpToJSObject([
				[
					'name' => Loc::getMessage('TMR_STATS'),
					'id' => 'SHOW_STATS_COLUMNS',
				],
				[
					'name' => Loc::getMessage('TMR_ADDITONAL'),
					'id' => 'SHOW_START_FINISH',
				],
				[
					'name' => Loc::getMessage('TM_WORKTIME_STATS_VIOLATIONS_MENU_TITLE'),
					'id' => 'SHOW_VIOLATIONS',
					'items' => [
						[
							'name' => Loc::getMessage('TM_WORKTIME_STATS_VIOLATIONS_MENU_TITLE_PERSONAL'),
							'id' => 'SHOW_VIOLATIONS_PERSONAL',
						],
						[
							'name' => Loc::getMessage('TM_WORKTIME_STATS_VIOLATIONS_COMMON_MENU_TITLE'),
							'id' => 'SHOW_VIOLATIONS_COMMON',
						],
					],
				],
			]);?>,
			schedulesData: <?= CUtil::PhpToJSObject($arResult['schedulesData']);?>,
			shiftedSchedules: <?= CUtil::PhpToJSObject($arResult['SHIFTED_SCHEDULES']);?>,
			gridId: '<?= CUtil::JSEscape($arResult['GRID_ID'])?>',
			isSlider: <?= CUtil::PhpToJSObject($arResult['isSlider']);?>,
			canDeleteSchedule: <?= CUtil::PhpToJSObject($arResult['canDeleteSchedule']);?>,
			canUpdateSchedule: <?= CUtil::PhpToJSObject($arResult['canUpdateSchedule']);?>,
			showViolationsPersonalName: 'SHOW_VIOLATIONS_PERSONAL',
			showViolationsCommonName: 'SHOW_VIOLATIONS_COMMON',
			showStatsColumnsName: 'SHOW_STATS_COLUMNS',
			showUserWithStats: <?php echo CUtil::PhpToJSObject($arResult['SHOW_START_FINISH']);?>,
			showStartFinishName: 'SHOW_START_FINISH',
			showStartFinish: <?php echo CUtil::PhpToJSObject($arResult['SHOW_START_FINISH']);?>,
			gridOptions: <?php echo CUtil::PhpToJSObject($arResult['gridOptions']);?>
		});
	});
</script>