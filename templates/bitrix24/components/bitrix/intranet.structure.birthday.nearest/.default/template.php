<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arMonths_r = array();
for ($i = 1; $i <= 12; $i++)
	$arMonths_r[$i] = ToLower(GetMessage('MONTH_'.$i.'_S'));
?>
<?
if ($arParams['SHOW_FILTER'] == 'Y'):
	$this->SetViewTarget("sidebar", 100);
	?>
	<form name="bx_birthday_filter" action="" method="get">
	<div class="sidebar-block">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">
			<div class="sidebar-block-title"><?= GetMessage('INTR_ISBN_TPL_FILTER_DEPARTMENT')?></div>
			<div class="filter-block">
				<div class="filter-field filter-field-user-department">
					<?
					CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], true);
					?>
				</div>
				<div class="filter-field-buttons">
					<input type="submit" value="<?= GetMessage("INTR_ISBN_TPL_FILTER_SUBMIT") ?>" class="filter-submit" />&nbsp;&nbsp;<input type="submit" value="<?= GetMessage("INTR_ISBN_TPL_FILTER_CANCEL") ?>" onclick="document.forms.bx_birthday_filter.department.selectedIndex=0;" class="filter-submit" />
				</div>
			</div>
		</div>
		<i class="r0"></i><i class="r1"></i><i class="r2"></i>
	</div>
	</form>
	<script type="text/javascript">
	window.onload = function() {document.forms.bx_birthday_filter.department.onchange = function() {this.form.submit()}}
	</script>
	<?
	$this->EndViewTarget();
endif;
?>
<div class="bx-birthday-layout">
<?
foreach ($arResult['USERS'] as $arUser)
{
	$birthday = FormatDateEx(
		$arUser['PERSONAL_BIRTHDAY'], 
		false, 
		$arParams['DATE_FORMAT'.($arParams['SHOW_YEAR'] == 'Y' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'M' ? '' : '_NO_YEAR')]
	);
	
	if ($arUser['IS_BIRTHDAY']) $birthday .= ' - '.GetMessage('INTR_ISBN_TPL_TODAY');
	
	$arUser['SUBTITLE'] = $birthday;
	$arUser['SUBTITLE_FEATURED'] = $arUser['IS_BIRTHDAY'] ? 'Y' : 'N';
	
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.system.person',
		'.default',
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