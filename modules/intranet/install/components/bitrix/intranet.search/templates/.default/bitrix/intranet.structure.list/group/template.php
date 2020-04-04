<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!is_array($arResult['USERS']) || !($USERS_CNT = count($arResult['USERS']))):
	if ($arResult['EMPTY_UNFILTERED_LIST'] == 'Y'):
		ShowNote(GetMessage('ISL_TPL_NOTE_UNFILTERED'));
	elseif ($arParams['SHOW_ERROR_ON_NULL'] == 'Y'):
		ShowError(GetMessage('ISL_TPL_NOTE_NULL'));
	endif;
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE');

	$arDeptsChain = array();
	$arCurrentDepth = array();
	
	foreach ($arResult['DEPARTMENTS'] as $arDept)
	{
		$arDeptsChain[$arDept['DEPTH_LEVEL']] = '<a href="'.$arParams['STRUCTURE_PAGE'].'?set_filter_'.$arParams['STRUCTURE_FILTER'].'=Y&'.$arParams['STRUCTURE_FILTER'].'_UF_DEPARTMENT='.$arDept['ID'].'">'.htmlspecialcharsbx($arDept['NAME']).'</a>';
	
		if (count($arDept['USERS']) > 0)
		{
			echo '<div class="users-departments-chain">' . (isset($arDept['DEPTH_LEVEL'])? implode('&nbsp;|&nbsp;', array_slice($arDeptsChain, 0, $arDept['DEPTH_LEVEL'])) : GetMessage('ISL_DEPARTMENT_NOT_FOUND')) . '</div>';
?>
	<div class="bx-users">
<?
			foreach ($arDept['USERS'] as $arUser)
			{
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
						'LIST_OBJECT' => $arParams['LIST_OBJECT'],
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
		}
	
	}

endif;
?>
