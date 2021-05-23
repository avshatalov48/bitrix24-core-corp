<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetPageProperty("BodyClass", "calendar-help-page");

?>
<script>
function goToEventList()
{
	BXMobileApp.onCustomEvent('mobile_calendar_first_page_trigger',{}, true); // Smart hack to prevent opening help page during first cached seccion of
	return false;
}

function goToEventHelp()
{
	app.openNewPage('/mobile/help/index.php?page=caldav');
	return false;
}
</script>

	<div class="calendar-help-main-block-aqua">
		<div class="calendar-help-main-block-aqua-container">
			<div class="calendar-help-title"><?= GetMessage('MB_CALENDAR_HELP_TITLE')?></div>
			<div class="calendar-help-participant-block" style="text-align:center; position: relative;">
				<img src="/bitrix/templates/mobile_app/images/calendar/synciphone.png" width="229" height="84">
				<div class="calendar-help-participant-block-fixing-week-abbr"><?= GetMessage('MB_CALENDAR_HELP_WEEK_DAY')?></div>
			</div>
			<br />
			<div class="calendar-help-title" style="font-size: 13px;"><?= GetMessage('MB_CALENDAR_HELP_PHRASE_2')?></div>
			<div class="calendar-help-p">
				<?= GetMessage('MB_CALENDAR_HELP_PHRASE_3')?>
			</div>
		</div>
	</div>
	<div class="calendar-help-main-block-aqua" style="padding:0;">
		<div class="calendar-help-main-block-aqua-container" style="padding:10px 0 5px;" onclick="return goToEventHelp();">
			<div class="calendar-help-row" style="text-align: center;">
				<span style="font-weight: bold;font-size: 19px;color: #2e4252;text-shadow:0 1px 1px #fff;"><?= GetMessage('MB_CALENDAR_HELP_HOW')?></span>
				<div class="calendar-help-arrow"></div>
			</div>
		</div>
	</div>

	<div class="calendar-event-button" id="mbcal-edit-del-but-cont">
		<a onclick="return goToEventList();" href="" class="calendar accept-button" style="float: left; width: 96%!important; margin-left: 2%;"><?= GetMessage('MB_CALENDAR_HELP_GO_TO_LIST_BUT')?></a>
		<div style="clear: both;"></div><br>
	</div>