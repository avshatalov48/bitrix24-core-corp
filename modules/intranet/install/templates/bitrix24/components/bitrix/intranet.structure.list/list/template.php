<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/employee.css");

if (!is_array($arResult['USERS']) || !($USERS_CNT = count($arResult['USERS']))):
	if ($arResult['EMPTY_UNFILTERED_LIST'] == 'Y'):
		echo "<p>".GetMessage('INTR_ISL_TPL_NOTE_UNFILTERED')."</p>";
	elseif ($arParams['SHOW_ERROR_ON_NULL'] == 'Y'):
		echo "<p>".GetMessage('INTR_ISL_TPL_NOTE_NULL')."</p>";
	endif;
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE', 'PERSONAL_PROFESSION');
?>

<div class="employee-table-wrap">
	<table id="employee-table" class="employee-table" cellspacing="0">
<?
	if (isset($arResult['FILTER_VALUES']['UF_DEPARTMENT']) && is_array($arResult['FILTER_VALUES']['UF_DEPARTMENT']))
	{
		foreach ($arResult['USERS'] as $key => $arUser)
		{
			if ($arResult['DEPARTMENT_HEAD'] == $arUser['ID'])
			{
				unset($arResult['USERS'][$key]);
			}
		}
	}
	
	foreach ($arResult['USERS'] as $key => $arUser):		
		$APPLICATION->IncludeComponent(
			'bitrix:intranet.system.person',
			'modern',
			array(
				'USER' => $arUser,
				'LIST_MODE' => $arParams['SHOW_USER'], 
				'USER_PROPERTY' => $arParams['USER_PROPERTY'],
				'PM_URL' => $arParams['PM_URL'],
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
				"PATH_TO_USER_EDIT" => $arParams["PATH_TO_USER_EDIT"],
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	endforeach;
?>
	</table>
</div>

<?=$arResult['USERS_NAV'];?>

<?endif?>
