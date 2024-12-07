<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);
?>
<script>
if (window.JCCalendarViewMonth)
	jsBXAC.SetViewHandler(new JCCalendarViewMonth());
else
	BX.loadScript(
		'/bitrix/components/bitrix/intranet.absence.calendar.view/templates/month/view.js',
		function() {jsBXAC.SetViewHandler(new JCCalendarViewMonth())}
	);
</script>