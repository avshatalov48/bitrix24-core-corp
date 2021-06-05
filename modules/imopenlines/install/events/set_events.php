<?
$langs = CLanguage::GetList();
while ($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/set_events.php", $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "IMOL_HISTORY_LOG",
		"NAME" => GetMessage("IMOL_HISTORY_LOG_NAME"),
		"DESCRIPTION" => GetMessage("IMOL_MAIL_PARAMS_DESC_NEW"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "IMOL_OPERATOR_ANSWER",
		"NAME" => GetMessage("IMOL_OPERATOR_ANSWER_NAME_NEW"),
		"DESCRIPTION" => GetMessage("IMOL_MAIL_PARAMS_DESC_NEW"),
	));

	$arSites = array();
	$sites = CSite::GetList('', '', Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IMOL_HISTORY_LOG",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#EMAIL_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:imopenlines.mail.history\",\"\",Array(\"TEMPLATE_TYPE\" => \"HISTORY\",\"TEMPLATE_SESSION_ID\" => \"{#TEMPLATE_SESSION_ID#}\",\"TEMPLATE_SERVER_ADDRESS\" => \"{#TEMPLATE_SERVER_ADDRESS#}\",\"TEMPLATE_ACTION_TITLE\" => \"{#TEMPLATE_ACTION_TITLE#}\",\"TEMPLATE_ACTION_DESC\" => \"{#TEMPLATE_ACTION_DESC#}\",\"TEMPLATE_WIDGET_DOMAIN\" => \"{#TEMPLATE_WIDGET_DOMAIN#}\",\"TEMPLATE_WIDGET_URL\" => \"{#TEMPLATE_WIDGET_URL#}\",\"TEMPLATE_LINE_NAME\" => \"{#TEMPLATE_LINE_NAME#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_imopenlines"
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "IMOL_OPERATOR_ANSWER",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#EMAIL_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:imopenlines.mail.history\",\"\",Array(\"TEMPLATE_TYPE\" => \"ANSWER\",\"TEMPLATE_SESSION_ID\" => \"{#TEMPLATE_SESSION_ID#}\",\"TEMPLATE_SERVER_ADDRESS\" => \"{#TEMPLATE_SERVER_ADDRESS#}\",\"TEMPLATE_ACTION_TITLE\" => \"{#TEMPLATE_ACTION_TITLE#}\",\"TEMPLATE_ACTION_DESC\" => \"{#TEMPLATE_ACTION_DESC#}\",\"TEMPLATE_WIDGET_DOMAIN\" => \"{#TEMPLATE_WIDGET_DOMAIN#}\",\"TEMPLATE_WIDGET_URL\" => \"{#TEMPLATE_WIDGET_URL#}\",\"TEMPLATE_LINE_NAME\" => \"{#TEMPLATE_LINE_NAME#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_imopenlines"
		));
	}
}
?>