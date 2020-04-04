<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

$this->setFrameMode(true);

if ($arResult['TASKS_ENABLED']):
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.iframe.popup",
		".default",
		array(
			"ON_TASK_ADDED" => "BX.DoNothing",
			"ON_TASK_CHANGED" => "BX.DoNothing",
			"ON_TASK_DELETED" => "BX.DoNothing",
		),
		null,
		array("HIDE_ICONS" => "Y")
	);
endif;

$arMess = array(
	'EMPLOYEE' => GetMessage('TMR_EMPLOYEE'),
	'OVERALL' => GetMessage('TMR_OVERALL'),
	'OVERALL_DAYS' => GetMessage('TMR_OVERALL_DAYS'),
	'OVERALL_VIOL' => GetMessage('TMR_OVERALL_VIOL'),
	'TMR_OVERALL_VIOL_GOOD' => GetMessage('TMR_OVERALL_VIOL_GOOD'),
	'ARRIVAL' => GetMessage('TMR_ARRIVAL'),
	'DEPARTURE' => GetMessage('TMR_DEPARTURE'),
	'EXP' => GetMessage('TMR_EXP'),
	'CAPTION_ARRIVAL' => GetMessage('TMR_CAPTION_ARRIVAL'),
	'CAPTION_DEPARTURE' => GetMessage('TMR_CAPTION_DEPARTURE'),
	'CAPTION_DURATION' => GetMessage('TMR_CAPTION_DURATION'),
	'FIXED' => GetMessage('TMR_FIXED'),
	'FIXED_AT' => GetMessage('TMR_FIXED_AT'),
	'FIXED_CAPTION' => GetMessage('TMR_FIXED_CAPTION'),
	'FIXED_APPROVER' => GetMessage('TMR_FIXED_APPROVER'),
	'APPROVE' => GetMessage('TMR_APPROVE'),
	'APPROVE' => GetMessage('TMR_APPROVE'),
	'SAVE' => GetMessage('MAIN_SAVE'),
	'CLOSE' => GetMessage('TMR_CLOSE'),
	'LEAKS_CAPTION' => GetMessage('TMR_LEAKS_CAPTION'),
	'FILTER_ARRIVAL' => GetMessage('TMR_FILTER_ARRIVAL'),
	'FILTER_DEPARTURE' => GetMessage('TMR_FILTER_DEPARTURE'),
	'FILTER_LABEL_ADD' => GetMessage('TMR_FILTER_LABEL_ADD'),
	'FILTER_LABEL_DPT' => GetMessage('TMR_FILTER_LABEL_DPT'),
	'HINT_TITLE' => GetMessage('TMR_HINT_TITLE'),
	'HINT_MAX_START' => GetMessage('TMR_HINT_MAX_START'),
	'HINT_MIN_FINISH' => GetMessage('TMR_HINT_MIN_FINISH'),
	'HINT_MIN_DURATION' => GetMessage('TMR_HINT_MIN_DURATION'),
	'DAY_NOT_FINISHED' => GetMessage('TMR_DAY_NOT_FINISHED'),
	'HEAD' => GetMessage('TMR_HEAD'),
	'HINT_DISABLED' => GetMessage('TMR_HINT_DISABLED'),
	'HINT_FREE' => GetMessage('TMR_HINT_FREE'),
	'MARK_N'=>GetMessage("TMR_NO_MARK"),
	'MARK_G'=>GetMessage("TMR_GOOD_MARK"),
	'MARK_B'=>GetMessage("TMR_BAD_MARK"),
	'NONE'=>GetMessage("TMR_NONE"),
	'DAY'=>GetMessage("TMR_DAY"),
	'WEEK'=>GetMessage("TMR_WEEK"),
	'MONTH'=>GetMessage("TMR_MONTH"),
	'TIME'=>GetMessage("TMR_TIME"),
	'PARENT'=>GetMessage("TMR_PARENT"),
	'DATE'=>GetMessage("TMR_DATE"),
	'PERIOD'=>GetMessage("TMR_PERIOD"),
	'TMR_DESC'=>GetMessage("TMR_DESC"),
	'TMR_EVERY_DAY'=>GetMessage("TMR_EVERY_DAY"),
	'TMR_EVERY_WEEK'=>GetMessage("TMR_EVERY_WEEK"),
	'TMR_EVERY_MONTH'=>GetMessage("TMR_EVERY_MONTH"),
	'TMR_INDAY_FULL_1'=>GetMessage("TMR_INDAY_FULL_1"),
	'TMR_INDAY_FULL_2'=>GetMessage("TMR_INDAY_FULL_2"),
	'TMR_INDAY_FULL_3'=>GetMessage("TMR_INDAY_FULL_3"),
	'TMR_INDAY_FULL_4'=>GetMessage("TMR_INDAY_FULL_4"),
	'TMR_INDAY_FULL_5'=>GetMessage("TMR_INDAY_FULL_5"),
	'TMR_INDAY_FULL_6'=>GetMessage("TMR_INDAY_FULL_6"),
	'TMR_INDAY_FULL_7'=>GetMessage("TMR_INDAY_FULL_7"),
	'TMR_DAY_FULL_1'=>GetMessage("TMR_DAY_FULL_2"),
	'TMR_DAY_FULL_2'=>GetMessage("TMR_DAY_FULL_3"),
	'TMR_DAY_FULL_3'=>GetMessage("TMR_DAY_FULL_4"),
	'TMR_DAY_FULL_4'=>GetMessage("TMR_DAY_FULL_5"),
	'TMR_DAY_FULL_5'=>GetMessage("TMR_DAY_FULL_6"),
	'TMR_DAY_FULL_6'=>GetMessage("TMR_DAY_FULL_7"),
	'TMR_DAY_FULL_7'=>GetMessage("TMR_DAY_FULL_1"),
	'TMR_INDATE'=>GetMessage("TMR_INDATE"),
	'TMR_BEGIN_DATE'=>GetMessage("TMR_BEGIN_DATE"),
	'TMR_DAY_WEEK'=>GetMessage("TMR_DAY_WEEK"),
	'TMR_DATE_MONTH'=>GetMessage("TMR_DATE_MONTH"),
	'TMR_MARK'=>GetMessage("TMR_MARK"),
	'TMR_REPORT_COUNT'=>GetMessage("TMR_REPORT_COUNT"),
	'TMR_REPORT_COUNT_MARK'=>GetMessage("TMR_REPORT_COUNT_MARK"),
	'TMR_REPORT'=>GetMessage("TMR_REPORT"),
	'TMR_NOT_ACCEPT'=>GetMessage("TMR_NOT_ACCEPT"),
	'TMR_REPORT_APPROVER'=>GetMessage("TMR_REPORT_APPROVER"),
	'TMR_ACCEPT_DATE'=>GetMessage("TMR_ACCEPT_DATE"),
	'TMR_PARENT'=>GetMessage("TMR_PARENT"),
	'TMR_SUCCESS'=>GetMessage("TMR_SUCCESS"),
	'TM_SETTINGS_REPORT'=>GetMessage("TM_SETTINGS_REPORT")
);

$cur_date_m = date('n'); $cur_date_y = date('Y');
?>
<div class="tm-report-layout" id="bx_tm_report">
	<div class="webform tm-filter-webform">
		<form name="REPORT_FILTER">
			<div class="webform-round-corners webform-additional-fields tm-report">
				<div class="webform-corners-top">
					<div class="webform-left-corner"></div>
					<div class="webform-right-corner"></div>
				</div>

				<div class="webform-content tm-filter">
					<span class="tm-filter-item filter-date tm-filter-item-first" id="tm-filter-date">
						<a href="javascript:void(0)" onclick="this.blur();window.BXTMREPORT.changeMonth(-1)" class="filter-date-link filter-date-link-left"></a>
						<span class="fiter-date-text" id="tm_datefilter_title" onclick="BX.calendar({node: this, field: 'bx_goto_date', bTime: false, callback: jsCalendarInsertDate});"><?=GetMessage('TMR_MONTH_'.$cur_date_m)?> <?=$cur_date_y?></span>
						<input type="hidden" name="bx_goto_date" id="bx_goto_date" value="<?=ConvertTimeStamp()?>" />
<?
$APPLICATION->IncludeComponent('bitrix:main.calendar', '', array('SHOW_INPUT' => 'Y', 'SHOW_TIME' => 'N', 'HIDE_TIMEBAR' => 'Y', 'INPUT_NAME' => 'bx_goto_date', 'SILENT'=>'Y'), null, array('HIDE_ICONS' => 'Y'));
?>
<script type="text/javascript">
function jsCalendarInsertDate(value)
{
	if (BX.type.isDate(value))
		value = value.valueOf();
	else
		value = parseInt(value);

	var date = new Date(value);
	if (date.getMonth() == BXTMREPORT.SETTINGS.DATE_START.getMonth() && date.getYear() == BXTMREPORT.SETTINGS.DATE_START.getYear())
	{
		var cell = BXTMREPORT.PARTS.LAYOUT_COLS.DATA.firstChild.tHead.rows[0].cells[date.getDate()-1];
		var q = cell.offsetLeft - BXTMREPORT.PARTS.LAYOUT_COLS.DATA.offsetWidth + Math.ceil(cell.offsetWidth * 1.6);
		BXTMREPORT.PARTS.LAYOUT_COLS.DATA.scrollLeft = q > 0 ? q : 0;
	}
	else
	{
		var dm = (date.getFullYear()-BXTMREPORT.SETTINGS.DATE_START.getFullYear()) * 12;
		dm += date.getMonth() - BXTMREPORT.SETTINGS.DATE_START.getMonth();
		BXTMREPORT.changeMonth(dm)
	}
}
</script>


						<a href="javascript:void(0)" onclick="this.blur();window.BXTMREPORT.changeMonth(1)"  class="filter-date-link filter-date-link-right"></a>
						<a href="javascript:void(0)" class="filter-date-link filter-date-link-calendar" onclick="this.blur();window.BXTMREPORT.setToday(); return false;" title="<?=htmlspecialcharsbx(GetMessage('TMR_SET_TODAY'))?>"></a>
					</span>
					<input type="hidden" name="month" value="<?=$cur_date_m;?>" />
					<input type="hidden" name="year" value="<?=$cur_date_y;?>" />
					<span class="tm-filter-item">
						<input type="checkbox" name="stats" id="stats" onclick="window.BXTMREPORT.toggleStats(this.checked);" checked="checked" /><label for="stats"><?=GetMessage('TMR_STATS');?></label>
					</span>


<?
if (count($arResult['arAccessUsers']['READ']) > 1 || $arResult['arAccessUsers']['READ'][0] == '*'):
?>
			<?if (count($arResult['arDirectUsers'])>1) $display = "inline-block"; else $display = "none";?>
					<span class="tm-filter-item inactive" style = "display:<?=$display;?>">
						<select name="show_all" class="inactive" onmousedown="BX.removeClass(this.parentNode, 'inactive')" onchange="window.BXTMREPORT.Filter();">
							<option value="Y"><?=GetMessage('TMR_FILTER_SHOW_ALL_Y')?></option>
							<option <?=(($arResult['SHOW_ALL'] == "N")?"selected":"")?> value="N"><?=GetMessage('TMR_FILTER_ONLY_DIRECT')?></option>
						</select>
						<a href="javascript:void(0)" class="filter-reset" onclick="this.blur(); document.forms.REPORT_FILTER.show_all.value = 'Y'; window.BXTMREPORT.Filter(); return false;"></a>
					</span>
					<span class="tm-filter-item inactive">
<?
	function __tmr_replace($str)
	{
		$str = preg_replace(
			'/<option([^>]*)>'.GetMessage('MAIN_NO').'<\/option>/i'.BX_UTF_PCRE_MODIFIER,
			'<option\\1>'.GetMessage('TMR_FILTER_DEPT_0').'</option>',
			$str
		);

		$str = preg_replace('/class="([^"]*)"/i', 'class="\\1" onchange="window.BXTMREPORT.Filter();"', $str);

		return $str;
	}

	CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], true, false, '__tmr_replace');
?>
<script type="text/javascript">
	document.forms.REPORT_FILTER.department.value = "<?=$arResult["DEPARTMENT_ID"];?>";
</script>
<a href="javascript:void(0);" class="filter-reset" onclick="this.blur(); document.forms.REPORT_FILTER.department.value = ''; window.BXTMREPORT.Filter(); return false;"></a>
					</span>
<span class="tm-settings-item" id="TMBUTTON" onclick="BXTMREPORT.InitSettingMode(this);">
	<span class="tm-settings-l"></span><span class="tm-settings-c"><span class="tm-settings-icon"></span><?=GetMessage("TM_SETTINGS")?></span><span class="tm-settings-r"></span>
</span>
<?
endif;
?>
				</div>


				<div class="webform-corners-bottom">
					<div class="webform-left-corner"></div>
					<div class="webform-right-corner"></div>
				</div>

			</div>
		</form>
	</div>
	<div id="bx-report-overdue" style="display:none" class="bx-tm-overdue"></div>
</div>
<script type="text/javascript">
window.BXTMREPORT = new JCTimeManReport('bx_tm_report', {
	DEPARTMENTS: 'tm_report_conrol_departments',
	FILTER: 'REPORT_FILTER',
	START_SHOW_ALL:"<?=$arResult["SHOW_ALL"];?>",
	START_DEPARTMENT:"<?=$arResult["DEPARTMENT_ID"];?>",
	DATESELECTOR: 'bx_tm_report_dateselector',
	MONTHS: [<?for($i=1;$i<13;$i++){echo ($i==1 ? '' : ','),"'",CUtil::JSEscape(GetMessage('TMR_MONTH_'.$i)),"'";}?>],
	DAYS: [<?for($i=1;$i<8;$i++){echo ($i==1 ? '' : ','),"'",CUtil::JSEscape(GetMessage('TMR_DAY_'.$i)),"'";}?>],
	LANG: <?=CUtil::PhpToJsObject($arMess)?>,
	SITE_ID: '<?=SITE_ID?>'
})</script>


<div style="clear: both;"></div>
