<?
$langs = CLanguage::GetList(($b=""), ($o=""));
while($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile(__FILE__, $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "CONTROLLER_MEMBER_REGISTER",
		"NAME" => GetMessage("CONTROLLER_MEMBER_REGISTER_NAME"),
		"DESCRIPTION" => GetMessage("CONTROLLER_MEMBER_REGISTER_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "CONTROLLER_MEMBER_CLOSED",
		"NAME" => GetMessage("CONTROLLER_MEMBER_CLOSED_NAME"),
		"DESCRIPTION" => GetMessage("CONTROLLER_MEMBER_CLOSED_DESC"),
	));

/* Message templates not yet ready
	$arSites = array();
	$sites = CSite::GetList(($b=""), ($o=""), Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "CONTROLLER_MEMBER_REGISTER",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => Get Message("CONTROLLER_MEMBER_REGISTER_SUBJECT"),
			"MESSAGE" => Get Message("CONTROLLER_MEMBER_REGISTER_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "CONTROLLER_MEMBER_CLOSED",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"BCC" => "",
			"SUBJECT" => Get Message("CONTROLLER_MEMBER_CLOSED_SUBJECT"),
			"MESSAGE" => Get Message("CONTROLLER_MEMBER_CLOSED_MESSAGE"),
			"BODY_TYPE" => "text",
		));
	}
*/
}
?>