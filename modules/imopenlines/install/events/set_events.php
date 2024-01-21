<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$langs = \CLanguage::GetList();
while ($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/set_events.php", $lid);

	$et = new \CEventType;
	$et->Add([
		"LID" => $lid,
		"EVENT_NAME" => "IMOL_HISTORY_LOG",
		"NAME" => Loc::getMessage("IMOL_HISTORY_LOG_NAME", null, $lid),
		"DESCRIPTION" => Loc::getMessage("IMOL_MAIL_PARAMS_DESC_NEW", null, $lid),
	]);

	$et = new \CEventType;
	$et->Add([
		"LID" => $lid,
		"EVENT_NAME" => "IMOL_OPERATOR_ANSWER",
		"NAME" => Loc::getMessage("IMOL_OPERATOR_ANSWER_NAME_NEW", null, $lid),
		"DESCRIPTION" => Loc::getMessage("IMOL_MAIL_PARAMS_DESC_NEW", null, $lid),
	]);

	$arSites = array();
	$sites = \CSite::GetList('', '', ["LANGUAGE_ID" => $lid]);
	while ($site = $sites->Fetch())
	{
		$arSites[] = $site["LID"];
	}

	if (count($arSites) > 0)
	{
		$emess = new \CEventMessage;
		$emess->Add([
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IMOL_HISTORY_LOG",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#EMAIL_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:imopenlines.mail.history\",\"\",Array(\"TEMPLATE_TYPE\" => \"HISTORY\",\"TEMPLATE_SESSION_ID\" => \"{#TEMPLATE_SESSION_ID#}\",\"TEMPLATE_SERVER_ADDRESS\" => \"{#TEMPLATE_SERVER_ADDRESS#}\",\"TEMPLATE_ACTION_TITLE\" => \"{#TEMPLATE_ACTION_TITLE#}\",\"TEMPLATE_ACTION_DESC\" => \"{#TEMPLATE_ACTION_DESC#}\",\"TEMPLATE_WIDGET_DOMAIN\" => \"{#TEMPLATE_WIDGET_DOMAIN#}\",\"TEMPLATE_WIDGET_URL\" => \"{#TEMPLATE_WIDGET_URL#}\",\"TEMPLATE_LINE_NAME\" => \"{#TEMPLATE_LINE_NAME#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_imopenlines"
		]);

		$emess = new \CEventMessage;
		$emess->Add([
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IMOL_OPERATOR_ANSWER",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#EMAIL_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:imopenlines.mail.history\",\"\",Array(\"TEMPLATE_TYPE\" => \"ANSWER\",\"TEMPLATE_SESSION_ID\" => \"{#TEMPLATE_SESSION_ID#}\",\"TEMPLATE_SERVER_ADDRESS\" => \"{#TEMPLATE_SERVER_ADDRESS#}\",\"TEMPLATE_ACTION_TITLE\" => \"{#TEMPLATE_ACTION_TITLE#}\",\"TEMPLATE_ACTION_DESC\" => \"{#TEMPLATE_ACTION_DESC#}\",\"TEMPLATE_WIDGET_DOMAIN\" => \"{#TEMPLATE_WIDGET_DOMAIN#}\",\"TEMPLATE_WIDGET_URL\" => \"{#TEMPLATE_WIDGET_URL#}\",\"TEMPLATE_LINE_NAME\" => \"{#TEMPLATE_LINE_NAME#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_imopenlines"
		]);
	}
}
