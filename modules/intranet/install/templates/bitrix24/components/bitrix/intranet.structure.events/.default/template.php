<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['INTRANET_TOOLBAR']->Show();
?>
<?
if ($arParams['SHOW_FILTER'] == 'Y'):
	$this->SetViewTarget("sidebar", 100);
	?>
	<form name="bx_events_filter" action="" method="get">
	<div class="sidebar-block">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">
			<div class="sidebar-block-title"><?= GetMessage('INTR_ISE_TPL_FILTER_DEPARTMENT')?></div>
			<div class="filter-block">
				<div class="filter-field filter-field-user-department">
					<?
					CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], true);
					?>
				</div>
				<div class="filter-field-buttons">
					<input type="submit" value="<?= GetMessage("INTR_ISE_TPL_FILTER_SUBMIT") ?>" class="filter-submit" />&nbsp;&nbsp;<input type="submit" value="<?= GetMessage("INTR_ISE_TPL_FILTER_CANCEL") ?>" onclick="document.forms.bx_events_filter.department.selectedIndex=0;" class="filter-submit" />
				</div>
			</div>
		</div>
		<i class="r0"></i><i class="r1"></i><i class="r2"></i>
	</div>
	</form>
	<script>window.onload = function() {document.forms.bx_events_filter.department.onchange = function() {this.form.submit()}}</script>
	<?
	$this->EndViewTarget();
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
