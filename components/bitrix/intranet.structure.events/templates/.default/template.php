<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['INTRANET_TOOLBAR']->Show();
?>
<?
if ($arParams['SHOW_FILTER'] == 'Y'):
?>
<table class="bx-users-toolbar"><tr><td class="bx-users-toolbar-last">
<form name="bx_events_filter" action="" method="get">
<?echo GetMessage('INTR_ISE_TPL_FILTER_DEPARTMENT')?>:
<?
	CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], true);
?>
	<input type="submit" value="<?echo GetMessage('INTR_ISE_TPL_FILTER_SUBMIT')?>" />
</form>
<script>window.onload = function() {document.forms.bx_events_filter.department.onchange = function() {this.form.submit()}}</script>
</td></tr></table>
<?
endif;
?>
<?
if (!is_array($arResult['ENTRIES']) || !($USERS_CNT = count($arResult['ENTRIES']))):
	ShowError(GetMessage('INTR_ISE_TPL_NOTE_NULL'));
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE');

if ($arParams['SHOW_NAV_TOP'] == 'Y'):
?>
<div class="bx-users-nav"><?echo $arResult['ENTRIES_NAV'];?></div>
<?else:?>
<a name="nav_start"></a>
<?
endif;
?>
<div class="bx-events-layout">
<?
foreach ($arResult['ENTRIES'] as $arEntry)
{
	$arUser = $arResult['USERS'][$arEntry['PROPERTY_USER_VALUE']];
	
	$arUser['UF_DEPARTMENT'] = array($arEntry['PROPERTY_DEPARTMENT_VALUE'] => $arResult['DEPARTMENTS'][$arEntry['PROPERTY_DEPARTMENT_VALUE']]);
	$arUser['WORK_POSITION'] = $arEntry['PROPERTY_POST_VALUE'];
	
	$arUser['SUBTITLE'] = FormatDateEx($arEntry['DATE_ACTIVE_FROM'], false, $arParams['DATE_FORMAT'])
		.' - '
		.ToLower(
			$arEntry['PROPERTY_STATE_VALUE'] 
			? $arEntry['PROPERTY_STATE_VALUE'] 
			: ($arEntry['PREVIEW_TEXT'] ? $arEntry['PREVIEW_TEXT'] : $arEntry['NAME'])
		);

	$APPLICATION->IncludeComponent(
		'bitrix:intranet.system.person',
		'',
		array(
			'USER' => $arUser,
			'USER_PROPERTY' => $arParams['USER_PROPERTY'],
			'PM_URL' => $arParams['PM_URL'],
			'STRUCTURE_PAGE' => $arParams['STRUCTURE_PAGE'],
			'STRUCTURE_FILTER' => $arParams['STRUCTURE_FILTER'],
			'USER_PROP' => $arResult['USER_PROP'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'SHOW_LOGIN' => $arParams['SHOW_LOGIN'],
			"DATE_FORMAT" => $arParams["DATE_FORMAT"],
			"DATE_FORMAT_NO_YEAR" => $arParams["DATE_FORMAT_NO_YEAR"],
			"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
			"SHOW_YEAR" => $arParams["SHOW_YEAR"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
			"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}
?>
</div>
<?
if ($arParams['SHOW_NAV_BOTTOM'] == 'Y'):
?>
<div class="bx-users-nav"><?echo $arResult['ENTRIES_NAV'];?></div>
<?
endif;

endif;
?>
