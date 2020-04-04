<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult["MENU_VARIABLES"] = array();
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/result_modifier.php")));
__IncludeLang($file);

if ($this->__page !== "menu"):
	$this->__component->__page_webdav_template = $this->__page;
	$sTempatePage = $this->__page;
	$sTempateFile = $this->__file;
	
	if ($arParams["SHOW_WEBDAV"] == "Y")
	{
		$url_help = $arResult["URL_TEMPLATES"]["help"];
		$url_base = $arParams["BASE_URL"];
		$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/informer.php")));
		include_once($file);
	}
	
	$this->__component->IncludeComponentTemplate("menu");
	$this->__page = $sTempatePage;
	$this->__file = $sTempateFile;
?>
<script type="text/javascript">
if (typeof(phpVars) != "object")
	phpVars = {};
if (!phpVars.titlePrefix)
	phpVars.titlePrefix = '<?=CUtil::addslashes(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - ';
if (!phpVars.messLoading)
	phpVars.messLoading = '<?=CUtil::addslashes(GetMessage("WD_LOADING"))?>';
if (!phpVars.ADMIN_THEME_ID)
	phpVars.ADMIN_THEME_ID = '.default';
if (!phpVars.bitrix_sessid)
	phpVars.bitrix_sessid = '<?=bitrix_sessid()?>';
if (!phpVars.cookiePrefix)
	phpVars.cookiePrefix = '<?=CUtil::JSEscape(COption::GetOptionString("main", "cookie_name", "BITRIX_SM"))?>';
if (typeof oObjectWD != "object")
	var oObjectWD = {};
</script>
<?
	$arResult["MENU_VARIABLES"] = $this->__component->__webdav_values;
	if (!is_array($arResult["MENU_VARIABLES"])):
		return false;
	endif;
else:
	return true;
endif;
?>