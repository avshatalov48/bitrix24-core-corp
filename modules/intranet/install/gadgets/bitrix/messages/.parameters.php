<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:socialnetwork.events_dyn", $arCurrentValues);

$arParameters = Array(
	"PARAMETERS" => Array(
		"PATH_TO_USER" => $arComponentProps["PARAMETERS"]["PATH_TO_USER"],
		"PATH_TO_GROUP" => $arComponentProps["PARAMETERS"]["PATH_TO_GROUP"],
		"PATH_TO_MESSAGE_FORM" => $arComponentProps["PARAMETERS"]["PATH_TO_MESSAGE_FORM"],
		"PATH_TO_MESSAGE_FORM_MESS" => $arComponentProps["PARAMETERS"]["PATH_TO_MESSAGE_FORM_MESS"],
		"PATH_TO_MESSAGES_CHAT" => $arComponentProps["PARAMETERS"]["PATH_TO_MESSAGES_CHAT"],
		"PATH_TO_SMILE" => $arComponentProps["PARAMETERS"]["PATH_TO_SMILE"],
		"AJAX_LONG_TIMEOUT" =>$arComponentProps["PARAMETERS"]["AJAX_LONG_TIMEOUT"],
		"MESSAGE_VAR" => $arComponentProps["PARAMETERS"]["MESSAGE_VAR"],
		"PAGE_VAR" => $arComponentProps["PARAMETERS"]["PAGE_VAR"],
		"USER_VAR" => $arComponentProps["PARAMETERS"]["USER_VAR"],
		"INBOX_URL" => Array(
			"NAME" => GetMessage("GD_MESSAGES_P_URL_INBOX"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/messages/input/"
			),
		"SENT_URL" => Array(
			"NAME" => GetMessage("GD_MESSAGES_P_URL_SENT"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/messages/output/"
			),
	),
	"USER_PARAMETERS" => Array(
	),
);

$arParameters["PARAMETERS"]["PATH_TO_USER"]["DEFAULT"]	=	"/company/personal/user/#user_id#/";
$arParameters["PARAMETERS"]["PATH_TO_GROUP"]["DEFAULT"]	=	"/company/personal/group/#group_id#/";
$arParameters["PARAMETERS"]["PATH_TO_MESSAGE_FORM"]["DEFAULT"]	=	"/company/personal/messages/form/#user_id#/";
$arParameters["PARAMETERS"]["PATH_TO_MESSAGES_CHAT"]["DEFAULT"]	=	"/company/personal/messages/chat/#user_id#/";
$arParameters["PARAMETERS"]["PATH_TO_SMILE"]["DEFAULT"]	=	"/bitrix/images/socialnetwork/smile/";
$arParameters["PARAMETERS"]["PATH_TO_MESSAGE_FORM_MESS"]["DEFAULT"]	= "/company/personal/messages/form/#user_id#/#message_id#/";
$arParameters["PARAMETERS"]["MESSAGE_VAR"]["DEFAULT"]	=	"message_id";
$arParameters["PARAMETERS"]["PAGE_VAR"]["DEFAULT"]	=	"page";
$arParameters["PARAMETERS"]["USER_VAR"]["DEFAULT"]	=	"user_id";
?>
