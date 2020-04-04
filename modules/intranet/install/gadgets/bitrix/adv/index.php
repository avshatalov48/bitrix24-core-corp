<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["TYPE"] = (isset($arGadgetParams["TYPE"])?$arGadgetParams["TYPE"]:"LEFT");

if(!CModule::IncludeModule("advertising"))
	return false;
?>
<div class="gdadv" style="text-align: center;">
<?
$APPLICATION->IncludeComponent(
	"bitrix:advertising.banner",
	".default",
	Array(
		"TYPE" => $arGadgetParams["TYPE"],
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "0"
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);

?>
</div>
