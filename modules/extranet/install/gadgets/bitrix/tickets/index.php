<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["PATH_TO_TICKET_EDIT"] = ($arGadgetParams["PATH_TO_TICKET_EDIT"] ? $arGadgetParams["PATH_TO_TICKET_EDIT"] : "/extranet/services/support.php?ID=#ID#");
$arGadgetParams["PATH_TO_TICKET_NEW"] = ($arGadgetParams["PATH_TO_TICKET_NEW"] ? $arGadgetParams["PATH_TO_TICKET_NEW"] : "/extranet/services/support.php?show_wizard=Y");

$_REQUEST["LAMP"] = $arGadgetParams["LAMP"];

$GLOBALS["APPLICATION"]->IncludeComponent(
	"bitrix:support.ticket.list", 
	"gadget", 
	Array(
		"TICKET_EDIT_TEMPLATE" => $arGadgetParams["PATH_TO_TICKET_EDIT"],
		"TICKETS_PER_PAGE" =>$arGadgetParams["ITEMS_COUNT"],
		"SET_PAGE_TITLE" => "N",
		"SITE_ID" => SITE_ID,
	),
	false,
	array("HIDE_ICONS" => "Y")
);



?>
<br>
<a href="<?=$arGadgetParams["PATH_TO_TICKET_NEW"]?>"><?=GetMessage("GD_TICKET_NEW")?></a><br />
