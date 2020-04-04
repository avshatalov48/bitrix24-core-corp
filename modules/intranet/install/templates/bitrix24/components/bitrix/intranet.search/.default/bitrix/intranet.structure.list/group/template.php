<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
$this->addExternalCss(SITE_TEMPLATE_PATH."/css/breadcrumbs.css");

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
		$arDeptsChain[$arDept['DEPTH_LEVEL']] = array("NAME"=>$arDept['NAME'], "ID"=>$arDept['ID']);//'<a class="breadcrumbs-item" href="'.$arParams['STRUCTURE_PAGE'].'?set_filter_'.$arParams['STRUCTURE_FILTER'].'=Y&'.$arParams['STRUCTURE_FILTER'].'_UF_DEPARTMENT='.$arDept['ID'].'">'.htmlspecialcharsbx($arDept['NAME']).'<i></i></a>';
		if (count($arDept['USERS']) > 0)
		{
		?>
			<div class="breadcrumbs" style="padding:10px 0 0 0">
				<?
				$ar_dep_breadcrumb = array_slice($arDeptsChain, 0, $arDept['DEPTH_LEVEL']);
				$ar_dep_breadcrumb_size = sizeof($ar_dep_breadcrumb)-1;
				foreach($ar_dep_breadcrumb as $key => $val)
				{
					?><a class="breadcrumbs-item" href="<?=$arParams['STRUCTURE_PAGE'].'?set_filter_'.$arParams['STRUCTURE_FILTER'].'=Y&'.$arParams['STRUCTURE_FILTER'].'_UF_DEPARTMENT='.$val['ID']?>"><?=htmlspecialcharsbx($val['NAME'])?><?if ($key != $ar_dep_breadcrumb_size):?><i></i><?endif;?></a><?
				}
				//echo implode('&nbsp;|&nbsp;', array_slice($arDeptsChain, 0, $arDept['DEPTH_LEVEL'])); ?>
			</div>
		<?
?>
<div>
	<table id="employee-table" class="employee-table" cellspacing="0">
<?
			foreach ($arDept['USERS'] as $arUser)
			{
				/*$APPLICATION->IncludeComponent(
					'bitrix:intranet.system.person',
					'modern',
					array(
						"USER" => $arUser,
						"USER_PROPERTY" => $arParams["USER_PROPERTY"],
						"LIST_MODE" => $arParams["SHOW_USER"],
						"PM_URL" => $arParams["PM_URL"],
						"STRUCTURE_PAGE" => $arParams["STRUCTURE_PAGE"],
						"STRUCTURE_FILTER" => $arParams["STRUCTURE_FILTER"],
						"USER_PROP" => $arResult["USER_PROP"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						"LIST_OBJECT" => $arParams["LIST_OBJECT"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
						"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"],
						"PATH_TO_USER_EDIT" => $arParams["PATH_TO_USER_EDIT"],
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);*/
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
			}
			
?>
	</table>
</div>
<?
		}
	
	}

endif;
?>
