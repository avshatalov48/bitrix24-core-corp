<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><script type="text/javascript">
if (typeof(phpVars) != "object")
	phpVars = {};
if (!phpVars.titlePrefix)
	phpVars.titlePrefix = '<?=CUtil::addslashes(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - ';
if (!phpVars.messLoading)
	phpVars.messLoading = '<?=CUtil::addslashes(GetMessage("WD_LOADING"))?>';
</script>