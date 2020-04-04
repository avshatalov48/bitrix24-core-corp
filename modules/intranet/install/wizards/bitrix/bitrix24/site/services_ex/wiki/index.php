<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!IsModuleInstalled("wiki"))
	return;

$rsSites = CSite::GetList($by="sort", $order="desc", array());
while ($arSite = $rsSites->Fetch())
{
	if ($arSite["ID"] != WIZARD_SITE_ID)
	{
		$arSiteIntranet = $arSite;
		break;
	}
}

if ($arSiteIntranet)
{
	if (IsModuleInstalled("forum"))
	{
		$SOCNET_FORUM_ID = COption::GetOptionString("wiki", "socnet_forum_id", false, $arSiteIntranet["ID"]);
		if (
			intval($SOCNET_FORUM_ID) > 0
			&& CModule::IncludeModule("forum")
		)
		{
			$arForumSites = CForumNew::GetSites($SOCNET_FORUM_ID);
			$arForumSites[WIZARD_SITE_ID] = WIZARD_SITE_DIR;

			$arForumFields = Array(
				"ACTIVE" => "Y",
				"SITES" => $arForumSites
			);
			CForumNew::Update($SOCNET_FORUM_ID, $arForumFields);

			COption::SetOptionString("wiki", "socnet_forum_id", $SOCNET_FORUM_ID, false, WIZARD_SITE_ID);
			COption::SetOptionString("wiki", "socnet_use_review", "Y", false, WIZARD_SITE_ID);
			COption::SetOptionString("wiki", "socnet_use_captcha", "Y", false, WIZARD_SITE_ID);
			COption::SetOptionString("wiki", "socnet_message_per_page", 10, false, WIZARD_SITE_ID);
		}
	} 
	else
		COption::SetOptionString("wiki", "socnet_use_review", "N", false, WIZARD_SITE_ID);
}
?>