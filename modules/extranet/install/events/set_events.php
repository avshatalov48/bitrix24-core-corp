<?
$langs = CLanguage::GetList();
while($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile(__FILE__, $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "EXTRANET_WG_TO_ARCHIVE",
		"NAME" => GetMessage("EXTRANET_WG_TO_ARCHIVE_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_WG_TO_ARCHIVE_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "EXTRANET_WG_FROM_ARCHIVE",
		"NAME" => GetMessage("EXTRANET_WG_FROM_ARCHIVE_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_WG_FROM_ARCHIVE_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "EXTRANET_INVITATION",
		"NAME" => GetMessage("EXTRANET_INVITATION_NAME"),
		"DESCRIPTION" => GetMessage("EXTRANET_INVITATION_DESC"),
	));
	
	
	$arSites = array();
	$sites = CSite::GetList("", "", Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "EXTRANET_WG_TO_ARCHIVE",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#MEMBER_EMAIL#",
			"BCC" => "",
			"SUBJECT" => GetMessage("EXTRANET_WG_TO_ARCHIVE_SUBJECT"),
			"MESSAGE" => GetMessage("EXTRANET_WG_TO_ARCHIVE_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "EXTRANET_WG_FROM_ARCHIVE",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#MEMBER_EMAIL#",
			"BCC" => "",
			"SUBJECT" => GetMessage("EXTRANET_WG_FROM_ARCHIVE_SUBJECT"),
			"MESSAGE" => GetMessage("EXTRANET_WG_FROM_ARCHIVE_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "EXTRANET_INVITATION",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL#",
			"BCC" => "",
			"SUBJECT" => GetMessage("EXTRANET_INVITATION_SUBJECT"),
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:intranet.template.mail\", \"\", array(\"USER_ID\" => \"{#USER_ID#}\",\"CHECKWORD\" => \"{#CHECKWORD#}\",\"SERVER_NAME\" => \"{#SERVER_NAME#}\",\"USER_TEXT\" => \"{#USER_TEXT#}\",\"USER_ID_FROM\" => \"{#USER_ID_FROM#}\",\"TEMPLATE_TYPE\" => \"EXTRANET_INVITATION\",\"FIELDS\" => \$arParams));?>",
			"BODY_TYPE" => "html",
		));		
	}
}
?>