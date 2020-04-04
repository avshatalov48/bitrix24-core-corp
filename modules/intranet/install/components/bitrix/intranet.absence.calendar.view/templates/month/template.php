<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<script type="text/javascript">
if (window.JCCalendarViewMonth)
	jsBXAC.SetViewHandler(new JCCalendarViewMonth());
else
	BX.loadScript(
		'/bitrix/components/bitrix/intranet.absence.calendar.view/templates/month/view.js', 
		function() {jsBXAC.SetViewHandler(new JCCalendarViewMonth())}
	);
</script>