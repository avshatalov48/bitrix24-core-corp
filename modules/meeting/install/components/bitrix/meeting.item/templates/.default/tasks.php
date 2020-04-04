<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @global CMain $APPLICATION
 */

if ($arResult['INCLUDE_LANG'])
{
	\Bitrix\Main\Localization\Loc::loadLanguageFile(dirname(__FILE__)."/template.php");
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.list',
	'',
	array(
		"FILTER" => array('ID' => count($arResult['ITEM']['TASKS']) > 0 ? $arResult['ITEM']['TASKS'] : array(-1)),
		"VIEW_MODE" => "list",
		"HIDE_VIEWS" => "Y",
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_SCROLL" => "N",
		"ITEMS_COUNT" => "10",
		"SET_NAVCHAIN" => "N",
		"PATH_TO_USER_PROFILE" => str_replace(
			array('#USER_ID#', '#ID#'),
			array('#user_id#', '#id#'),
			COption::GetOptionString('intranet', 'path_user', '', SITE_ID)
		),
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	null, array('HIDE_ICONS' => 'Y')
);
?>
<script type="text/javascript">
<?
foreach ($arResult['ITEM']['TASKS'] as $task_id):
?>
if (tasksMenuPopup[<?=$task_id?>])
{
	tasksMenuPopup[<?=$task_id?>].push({text:'<?=CUtil::JSEscape(GetMessage('MI_TASK_DETACH'))?>',title:'<?=CUtil::JSEscape(GetMessage('MI_TASK_DETACH_TITLE'))?>',className:"menu-popup-item-delete",onclick:function(e){detachTask(<?=$task_id?>); this.popupWindow.close();}});
}
<?
endforeach;
?>
</script>