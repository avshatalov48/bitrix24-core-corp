<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
//echo "<pre>";print_r($arResult);echo "</pre>";
CUtil::InitJSCore(Array('ajax','window'));?>
<?
if ($arResult["NEED_ACTIVATION"] || $arResult["IS_ACTIVATION"])
	include("activation_form.php");
else
	include("data_form.php");		
?>



