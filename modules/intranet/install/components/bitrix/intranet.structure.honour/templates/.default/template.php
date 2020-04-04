<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $INTRANET_TOOLBAR;
$INTRANET_TOOLBAR->Show();

/*
$arMonths_r = array();
for ($i = 1; $i <= 12; $i++)
	$arMonths_r[$i] = ToLower(GetMessage('MONTH_'.$i.'_S'));
*/
/*	if ($arParams['SHOW_FILTER'] == 'Y'):
?>
<table class="bx-users-toolbar"><tr><td class="bx-users-toolbar-last">
<form name="bx_birthday_filter" action="" method="get">
<?echo GetMessage('INTR_ISBN_TPL_FILTER_DEPARTMENT')?>:
<?
$APPLICATION->IncludeComponent(
	'bitrix:system.field.edit',
	'iblock_section',
	array(
		"arUserField" => $arResult['UF_DEPARTMENT_field'],
		'bVarsFromForm' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
)?>
	<input type="submit" value="Choose" />
</form>
<script>window.onload = function() {document.forms.bx_birthday_filter.department.onchange = function() {this.form.submit()}}</script>
</td></tr></table>
<?
	endif;*/
?>
<div class="bx-honour-layout">
<?
foreach ($arResult['ENTRIES'] as $arEntry)
//foreach ($arResult['USERS'] as $arUser)
{
	$arUser = $arResult['USERS'][$arEntry['PROPERTY_USER_VALUE']];

	$arUser['SUBTITLE'] = $arEntry['PREVIEW_TEXT'];
	$arUser['PREVIEW_TEXT_TYPE'] = $arEntry['PREVIEW_TEXT_TYPE'];

	if (!$arUser['SUBTITLE'])
	{
		$arUser['SUBTITLE'] = $arEntry['DETAIL_TEXT'];
		$arUser['PREVIEW_TEXT_TYPE'] = $arEntry['DETAIL_TEXT_TYPE'];
	}
	if (!$arUser['SUBTITLE'])
	{
		$arUser['SUBTITLE'] = $arEntry['NAME'];
		$arUser['PREVIEW_TEXT_TYPE'] = 'text';
	}

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
