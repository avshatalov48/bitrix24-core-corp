<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult["MENU_VARIABLES"] = array();
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/result_modifier.php")));
__IncludeLang($file);

if ($this->__page !== "menu" && $this->__page !== "element_upload_simple" && !isset($_REQUEST['AJAX_CALL']) && !isset($_REQUEST['ajax_call']) && !isset($_REQUEST['save_product_review'])):
?>
<script type="text/javascript">
if (typeof(phpVars) != "object")
	phpVars = {};
if (!phpVars.titlePrefix)
	phpVars.titlePrefix = '<?=CUtil::JSEscape(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))?> - ';
if (!phpVars.messLoading)
	phpVars.messLoading = '<?=CUtil::JSEscape(GetMessage("WD_LOADING"))?>';
if (!phpVars.ADMIN_THEME_ID)
	phpVars.ADMIN_THEME_ID = '.default';
if (!phpVars.bitrix_sessid)
	phpVars.bitrix_sessid = '<?=bitrix_sessid()?>';
if (!phpVars.cookiePrefix)
	phpVars.cookiePrefix = '<?=CUtil::JSEscape(COption::GetOptionString("main", "cookie_name", "BITRIX_SM"))?>';
if (typeof oObjectWD != "object")
	var oObjectWD = {};

if (typeof(phpVars2) != "object")
	phpVars2 = {};	
if (!phpVars2.messYes)
	phpVars2.messYes = '&nbsp;<?=CUtil::JSEscape(GetMessage("WD_Y"))?>&nbsp;';
if (!phpVars2.messNo)
	phpVars2.messNo = '&nbsp;<?=CUtil::JSEscape(GetMessage("WD_N"))?>&nbsp;';
</script>
<?
endif;
if (in_array($this->__page, array("section_edit_simple", "element_upload", "disk_element_upload", "element_upload_simple", "sections_dialog")) || isset($_REQUEST['AJAX_CALL'])): 
	$this->__component->__page_webdav_template = $this->__page;
	$this->__component->__template_is_buffering = false; 
elseif (in_array($this->__page, array("element_upload", "webdav_task_list"))): 
	$this->__component->__page_webdav_template = $this->__page;
	$this->__component->__page_webdav_chain_items = count($APPLICATION->arAdditionalChain); 
	$sTempatePage = $this->__page;
	$sTempateFile = $this->__file;
	$this->__component->IncludeComponentTemplate("menu");
	$this->__page = $sTempatePage;
	$this->__file = $sTempateFile;
elseif (!(in_array($this->__page, array("menu", "section_edit_simple", "disk_section_edit_simple")) || isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_call']) ||
	$this->__page == "webdav_bizproc_workflow_edit" && $_REQUEST["export_template"] = "Y" && check_bitrix_sessid())):
	ob_start();
	$this->__component->__page_webdav_template = $this->__page; 
	$this->__component->__page_webdav_chain_items = count($APPLICATION->arAdditionalChain); 
	$this->__component->__template_is_buffering = true; 
endif;
?>
