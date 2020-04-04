<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['INTRANET_TOOLBAR']->Show();
?>
<?
if (!is_array($arResult['USERS']) || !($USERS_CNT = count($arResult['USERS']))):
	if ($arResult['EMPTY_UNFILTERED_LIST'] == 'Y'):
		ShowNote(GetMessage('INTR_ISL_TPL_NOTE_UNFILTERED'));
	elseif ($arParams['SHOW_ERROR_ON_NULL'] == 'Y'):
		ShowError(GetMessage('INTR_ISL_TPL_NOTE_NULL'));
	endif;
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE');

	if ($arParams['SHOW_NAV_TOP'] == 'Y'):
?>
		<div class="bx-users-nav"><?echo $arResult['USERS_NAV'];?></div>
<?else:?>
<a name="nav_start"></a>
<?
	endif;
?>
<div class="bx-users">
<?
	foreach ($arResult['USERS'] as $key => $arUser):
		$arUser['IS_HEAD'] = false;
		if ($arResult['DEPARTMENT_HEAD'] == $arUser['ID'])
		{
			$arUser['SUBTITLE'] = GetMessage('INTR_ISL_TPL_HEAD');
			$arUser['IS_HEAD'] = true;
		}
		
		$APPLICATION->IncludeComponent(
			'bitrix:intranet.system.person',
			'',
			array(
				'USER' => $arUser,
				'USER_PROPERTY' => $arParams['USER_PROPERTY'],
				'PM_URL' => $arParams['PM_URL'],
				'PATH_TO_USER_EDIT' => $arParams['PATH_TO_USER_EDIT'],
				'STRUCTURE_PAGE' => $arParams['STRUCTURE_PAGE'],
				'STRUCTURE_FILTER' => $arParams['STRUCTURE_FILTER'],
				'USER_PROP' => $arResult['USER_PROP'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'SHOW_LOGIN' => $arParams['SHOW_LOGIN'],
				'LIST_OBJECT' => $arParams['LIST_OBJECT'],
				'SHOW_FIELDS_TOOLTIP' => $arParams['SHOW_FIELDS_TOOLTIP'],
				'USER_PROPERTY_TOOLTIP' => $arParams['USER_PROPERTY_TOOLTIP'],
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
	endforeach;
?>
</div>
<?
	if ($arParams['SHOW_NAV_BOTTOM'] == 'Y'):
?>
		<div class="bx-users-nav"><?echo $arResult['USERS_NAV'];?></div>
<?
	endif;
endif;
?>
