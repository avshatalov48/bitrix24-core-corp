<?
$langs = CLanguage::GetList();
while($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile(__FILE__, $lid);

	$arSites = array();
	$sites = CSite::GetList('', '', Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
	{
		$arSites[] = $site["LID"];
	}

	$et = new CEventType;

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "INTRANET_USER_INVITATION",
		"NAME" => GetMessage("INTRANET_USER_INVITATION_NAME"),
		"DESCRIPTION" => GetMessage("INTRANET_USER_INVITATION_DESC"),
	));
	
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "INTRANET_USER_ADD",
		"NAME" => GetMessage("INTRANET_USER_ADD_NAME"),
		"DESCRIPTION" => GetMessage("INTRANET_USER_ADD_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOCOMPLETE",
		"NAME" => GetMessage("INTRANET_MAILDOMAIN_NOCOMPLETE_NAME"),
		"DESCRIPTION" => GetMessage("INTRANET_MAILDOMAIN_NOCOMPLETE_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOMAILBOX",
		"NAME" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX_NAME"),
		"DESCRIPTION" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOMAILBOX2",
		"NAME" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX2_NAME"),
		"DESCRIPTION" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX2_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOREG",
		"NAME" => GetMessage("INTRANET_MAILDOMAIN_NOREG_NAME"),
		"DESCRIPTION" => GetMessage("INTRANET_MAILDOMAIN_NOREG_DESC"),
	));

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "INTRANET_USER_INVITATION",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => GetMessage("INTRANET_USER_INVITATION_SUBJECT"),
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:intranet.template.mail\", \"\", array(\"EMAIL_FROM\" => \"{#EMAIL_FROM#}\",\"EMAIL_TO\" => \"{#EMAIL_TO#}\",\"LINK\" => \"{#LINK#}\",\"USER_TEXT\" => \"{#USER_TEXT#}\",\"USER_ID_FROM\" => \"{#USER_ID_FROM#}\",\"TEMPLATE_TYPE\" => \"USER_INVITATION\",\"FIELDS\" => \$arParams));?>",
			"BODY_TYPE" => "html",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "INTRANET_USER_ADD",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => GetMessage("INTRANET_USER_ADD_SUBJECT"),
			"MESSAGE" => GetMessage("INTRANET_USER_ADD_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOCOMPLETE",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => GetMessage("INTRANET_MAILDOMAIN_NOCOMPLETE_SUBJECT"),
			"MESSAGE" => GetMessage("INTRANET_MAILDOMAIN_NOCOMPLETE_MESSAGE"),
			"BODY_TYPE" => "html",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOMAILBOX",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX_SUBJECT"),
			"MESSAGE" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX_MESSAGE"),
			"BODY_TYPE" => "html",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOMAILBOX2",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX2_SUBJECT"),
			"MESSAGE" => GetMessage("INTRANET_MAILDOMAIN_NOMAILBOX2_MESSAGE"),
			"BODY_TYPE" => "html",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "INTRANET_MAILDOMAIN_NOREG",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => GetMessage("INTRANET_MAILDOMAIN_NOREG_SUBJECT"),
			"MESSAGE" => GetMessage("INTRANET_MAILDOMAIN_NOREG_MESSAGE"),
			"BODY_TYPE" => "html",
		));
	}
}
?>