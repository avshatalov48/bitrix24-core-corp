<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);
?>
<script type="text/javascript">
if (window.JCCalendarViewDay)
	jsBXAC.SetViewHandler(new JCCalendarViewDay());
else
	BX.loadScript(
		'/bitrix/components/bitrix/intranet.absence.calendar.view/templates/day/view.js',
		function() {jsBXAC.SetViewHandler(new JCCalendarViewDay())}
	);
</script>