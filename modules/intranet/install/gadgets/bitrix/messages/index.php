<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["PATH_TO_USER"] = ($arGadgetParams["PATH_TO_USER"]?$arGadgetParams["PATH_TO_USER"]:"/company/personal/user/#user_id#/");
$arGadgetParams["PATH_TO_GROUP"] = ($arGadgetParams["PATH_TO_GROUP"]?$arGadgetParams["PATH_TO_GROUP"]:"/company/personal/group/#group_id#/");
$arGadgetParams["PATH_TO_MESSAGE_FORM"] = ($arGadgetParams["PATH_TO_MESSAGE_FORM"]?$arGadgetParams["PATH_TO_MESSAGE_FORM"]:"/company/personal/messages/form/#user_id#/");
$arGadgetParams["PATH_TO_MESSAGE_FORM_MESS"]	=	($arGadgetParams["PATH_TO_MESSAGE_FORM_MESS"]?$arGadgetParams["PATH_TO_MESSAGE_FORM_MESS"]:"/company/personal/messages/form/#user_id#/#message_id#/");
$arGadgetParams["PATH_TO_MESSAGES_CHAT"] = ($arGadgetParams["PATH_TO_MESSAGES_CHAT"]?$arGadgetParams["PATH_TO_MESSAGES_CHAT"]:"/company/personal/messages/chat/#user_id#/");
$arGadgetParams["PATH_TO_SMILE"] = ($arGadgetParams["PATH_TO_SMILE"]?$arGadgetParams["PATH_TO_SMILE"]:"/bitrix/images/socialnetwork/smile/");
$arGadgetParams["MESSAGE_VAR"] = ($arGadgetParams["MESSAGE_VAR"]?$arGadgetParams["MESSAGE_VAR"]:"message_id");
$arGadgetParams["PAGE_VAR"] = ($arGadgetParams["PAGE_VAR"]?$arGadgetParams["PAGE_VAR"]:"page");
$arGadgetParams["USER_VAR"] = ($arGadgetParams["USER_VAR"]?$arGadgetParams["USER_VAR"]:"user_id");
$arGadgetParams["INBOX_URL"] = ($arGadgetParams["INBOX_URL"]?$arGadgetParams["INBOX_URL"]:"/company/personal/messages/input/");
$arGadgetParams["SENT_URL"] = ($arGadgetParams["SENT_URL"]?$arGadgetParams["SENT_URL"]:"/company/personal/messages/output/");

$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:socialnetwork.events_dyn", ".default", array(
	"PATH_TO_USER" => $arGadgetParams["PATH_TO_USER"],
	"PATH_TO_GROUP" => $arGadgetParams["PATH_TO_GROUP"],
	"PATH_TO_MESSAGE_FORM" => "",
	"PATH_TO_MESSAGE_FORM_MESS" => $arGadgetParams["PATH_TO_MESSAGE_FORM_MESS"],
	"PATH_TO_MESSAGES_CHAT" => $arGadgetParams["PATH_TO_MESSAGES_CHAT"],
	"PATH_TO_SMILE" => $arGadgetParams["PATH_TO_SMILE"],
	"AJAX_LONG_TIMEOUT" => $arGadgetParams["AJAX_LONG_TIMEOUT"],
	"MESSAGE_VAR" => $arGadgetParams["MESSAGE_VAR"],
	"PAGE_VAR" => $arGadgetParams["PAGE_VAR"],
	"USER_VAR" => $arGadgetParams["USER_VAR"],
	"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
	"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
	),
	false,
	array("HIDE_ICONS" => "Y")
);

CModule::IncludeModule("socialnetwork");

$n1 = CSocNetMessages::GetList(array(), array("FROM_USER_ID" => $GLOBALS["USER"]->GetID(), "MESSAGE_TYPE" => "P", "FROM_DELETED" => "N"), array());
$n2 = CSocNetMessages::GetList(array(), array("TO_USER_ID" => $GLOBALS["USER"]->GetID(), "MESSAGE_TYPE" => "P", "TO_DELETED" => "N"), array());
?>
<a href="<?=$arGadgetParams["INBOX_URL"]?>"><?=GetMessage("GD_MESS_INBOX")?></a>: <?= $n2 ?><br />
<a href="<?=$arGadgetParams["SENT_URL"]?>"><?=GetMessage("GD_MESS_SENT")?></a>: <?= $n1 ?><br />
