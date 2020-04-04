<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("forum"))
	return;

$langFile = $_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix/portal/site/services/forum/lang/".LANGUAGE_ID."/index.php"; 
if (file_exists($langFile))
	__IncludeLang($langFile);

$arLanguages = Array();
$rsLanguage = CLanguage::GetList($by, $order, array());
while($arLanguage = $rsLanguage->Fetch())
	$arLanguages[] = $arLanguage["LID"];

// Forum group
$arGroupID = Array(
	"HIDDEN" => 0,
	"COMMENTS" => 0,
);

$dbExistsGroup = CForumGroup::GetListEx(array(), array("LID" => LANGUAGE_ID));
while ($arExistsGroup = $dbExistsGroup->Fetch())
{
	foreach ($arGroupID as $xmlID => $ID)
	{
		if ($arExistsGroup["NAME"] == GetMessage($xmlID."_GROUP_NAME") )
			$arGroupID[$xmlID] = $arExistsGroup["ID"];
	}
}

$sort = 1;

foreach ($arGroupID as $xmlID => $groupID)
{
	if ($groupID > 0)
		continue;

	$arNewGroup = Array("SORT" => $sort++, "LANG" => Array());
	foreach($arLanguages as $languageID)
	{
		$arMessages = CExtranetWizardServices::IncludeServiceLang("index.php", $languageID, $bReturnArray=true);

		$arNewGroup["LANG"][] = Array(
			"LID" => $languageID, 
			"NAME" => (array_key_exists($xmlID."_GROUP_NAME",$arMessages) ? $arMessages[$xmlID."_GROUP_NAME"] : GetMessage($xmlID."_GROUP_NAME")), 
			"DESCRIPTION" => (array_key_exists($xmlID."_GROUP_DESCRIPTION",$arMessages) ? $arMessages[$xmlID."_GROUP_DESCRIPTION"] : GetMessage($xmlID."_GROUP_DESCRIPTION"))
		);
	}

	$arGroupID[$xmlID] = CForumGroup::Add($arNewGroup);
}

$arForums = Array(

	Array(
		"XML_ID" => "USERS_AND_GROUPS",
		"NAME" => GetMessage("USERS_AND_GROUPS_EXTRANET_FORUM_NAME"),
		"DESCRIPTION" => GetMessage("USERS_AND_GROUPS_EXTRANET_FORUM_DESCRIPTION"),
		"SORT" => 101,
		"ACTIVE" => "Y",
		"ALLOW_HTML" => "N",
		"ALLOW_ANCHOR" => "Y",
		"ALLOW_BIU" => "Y",
		"ALLOW_IMG" => "Y",
		"ALLOW_LIST" => "Y",
		"ALLOW_QUOTE" => "Y",
		"ALLOW_CODE" => "Y",
		"ALLOW_FONT" => "Y",
		"ALLOW_SMILES" => "Y",
		"ALLOW_UPLOAD" => "Y",
		"ALLOW_NL2BR" => "N",
		"MODERATION" => "N",
		"ALLOW_MOVE_TOPIC" => "Y",
		"ORDER_BY" => "P",
		"ORDER_DIRECTION" => "DESC",
		"LID" => LANGUAGE_ID,
		"PATH2FORUM_MESSAGE" => "",
		"ALLOW_UPLOAD_EXT" => "",
		"FORUM_GROUP_ID" => $arGroupID["HIDDEN"],
		"ASK_GUEST_EMAIL" => "N",
		"USE_CAPTCHA" => "N",
		"SITES" => Array(
			WIZARD_SITE_ID => WIZARD_SITE_DIR,
		),
		"EVENT1" => "forum", 
		"EVENT2" => "message",
		"EVENT3" => "",
		"GROUP_ID" => Array(
			WIZARD_EXTRANET_ADMIN_GROUP => "Y",
		),
	),


	Array(
		"XML_ID" => "GROUPS_AND_USERS_FILES_COMMENTS",
		"NAME" => GetMessage("GROUPS_AND_USERS_FILES_COMMENTS_EXTRANET_NAME"),
		"DESCRIPTION" => GetMessage("GROUPS_AND_USERS_FILES_COMMENTS_EXTRANET_DECRIPTION"),
		"SORT" => 106,
		"ACTIVE" => "Y",
		"ALLOW_HTML" => "N",
		"ALLOW_ANCHOR" => "Y",
		"ALLOW_BIU" => "Y",
		"ALLOW_IMG" => "Y",
		"ALLOW_LIST" => "Y",
		"ALLOW_QUOTE" => "Y",
		"ALLOW_CODE" => "Y",
		"ALLOW_FONT" => "Y",
		"ALLOW_SMILES" => "Y",
		"ALLOW_UPLOAD" => "Y",
		"ALLOW_NL2BR" => "N",
		"MODERATION" => "N",
		"ALLOW_MOVE_TOPIC" => "Y",
		"ORDER_BY" => "P",
		"ORDER_DIRECTION" => "DESC",
		"LID" => LANGUAGE_ID,
		"PATH2FORUM_MESSAGE" => "",
		"ALLOW_UPLOAD_EXT" => "",
		"FORUM_GROUP_ID" => $arGroupID["COMMENTS"],
		"ASK_GUEST_EMAIL" => "N",
		"USE_CAPTCHA" => "N",
		"SITES" => Array(
			WIZARD_SITE_ID => WIZARD_SITE_DIR,
		),
		"EVENT1" => "forum", 
		"EVENT2" => "message",
		"EVENT3" => "",
		"GROUP_ID" => Array(
			WIZARD_EXTRANET_GROUP => "M",
			WIZARD_EXTRANET_ADMIN_GROUP => "Y",
		),
	), 	
	
	Array(
		"XML_ID" => "PHOTOGALLERY_COMMENTS",
		"NAME" => GetMessage("GROUPS_AND_USERS_PHOTOGALLERY_COMMENTS_EXTRANET_NAME"),
		"DESCRIPTION" => GetMessage("GROUPS_AND_USERS_PHOTOGALLERY_COMMENTS_EXTRANET_DECRIPTION"),
		"SORT" => 106,
		"ACTIVE" => "Y",
		"ALLOW_HTML" => "N",
		"ALLOW_ANCHOR" => "Y",
		"ALLOW_BIU" => "Y",
		"ALLOW_IMG" => "Y",
		"ALLOW_LIST" => "Y",
		"ALLOW_QUOTE" => "Y",
		"ALLOW_CODE" => "Y",
		"ALLOW_FONT" => "Y",
		"ALLOW_SMILES" => "Y",
		"ALLOW_UPLOAD" => "Y",
		"ALLOW_NL2BR" => "N",
		"MODERATION" => "N",
		"ALLOW_MOVE_TOPIC" => "Y",
		"ORDER_BY" => "P",
		"ORDER_DIRECTION" => "DESC",
		"LID" => LANGUAGE_ID,
		"PATH2FORUM_MESSAGE" => "",
		"ALLOW_UPLOAD_EXT" => "",
		"FORUM_GROUP_ID" => $arGroupID["COMMENTS"],
		"ASK_GUEST_EMAIL" => "N",
		"USE_CAPTCHA" => "N",
		"SITES" => Array(
			WIZARD_SITE_ID => WIZARD_SITE_DIR,
		),
		"EVENT1" => "forum", 
		"EVENT2" => "message",
		"EVENT3" => "",
		"GROUP_ID" => Array(
			WIZARD_EXTRANET_GROUP => "M",
			WIZARD_EXTRANET_ADMIN_GROUP => "Y",
		),
	), 	
	
);

$default_site_id = CSite::GetDefSite();
if (strlen($default_site_id) > 0)
{
	foreach ($arForums as $arForum)
	{
		$dbForum = CForumNew::GetList(Array(), Array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => $arForum["XML_ID"]));
		if ($arForumTmp = $dbForum->Fetch())
			continue;
		else
		{
			$dbForumDefault = CForumNew::GetList(Array(), Array("SITE_ID" => $default_site_id, "XML_ID" => $arForum["XML_ID"]));
			if ($arForumDefault = $dbForumDefault->Fetch())
			{
				$arSites = CForumNew::GetSites($arForumDefault["ID"]);
				$arSites[WIZARD_SITE_ID] = WIZARD_SITE_DIR;

				$arForumFields = Array(
					"ACTIVE" => $arForumDefault["ACTIVE"],
					"SITES" => $arSites
				);
				CForumNew::Update($arForumDefault["ID"], $arForumFields);
			}
			else
				$forumID = CForumNew::Add($arForum);
		}
	}
}

$UsersAndGroupsForumID = 0;
$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => "USERS_AND_GROUPS"));
if ($arRes = $dbRes->Fetch())
	$UsersAndGroupsForumID = $arRes["ID"];
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", Array("FORUM_ID" => $UsersAndGroupsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("FORUM_ID" => $UsersAndGroupsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("FORUM_ID" => $UsersAndGroupsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("FORUM_ID" => $UsersAndGroupsForumID));

$DocsSocnetCommentsForumID = 0;
$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => "GROUPS_AND_USERS_FILES_COMMENTS"));
if ($arRes = $dbRes->Fetch())
	$DocsSocnetCommentsForumID = $arRes["ID"];
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", Array("FILES_FORUM_ID" => $DocsSocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("FILES_FORUM_ID" => $DocsSocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("FILES_FORUM_ID" => $DocsSocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("FILES_FORUM_ID" => $DocsSocnetCommentsForumID));

$TasksSocnetCommentsForumID = 0;
$dbRes = CForumNew::GetListEx(array(), array('XML_ID' => 'intranet_tasks'));
if ($arRes = $dbRes->Fetch())
	$TasksSocnetCommentsForumID = $arRes["ID"];
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", Array("TASKS_FORUM_ID" => $TasksSocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("TASKS_FORUM_ID" => $TasksSocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("TASKS_FORUM_ID" => $TasksSocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("TASKS_FORUM_ID" => $TasksSocnetCommentsForumID));

$PhotogallerySocnetCommentsForumID = 0;
$dbRes = CForumNew::GetListEx(array(), array("XML_ID" => "PHOTOGALLERY_COMMENTS"));
if ($arRes = $dbRes->Fetch())
	$PhotogallerySocnetCommentsForumID = $arRes["ID"];
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", Array("PHOTOGALLERY_FORUM_ID" => $PhotogallerySocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("PHOTOGALLERY_FORUM_ID" => $PhotogallerySocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("PHOTOGALLERY_FORUM_ID" => $PhotogallerySocnetCommentsForumID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("PHOTOGALLERY_FORUM_ID" => $PhotogallerySocnetCommentsForumID));

if (strlen($default_site_id) > 0)
{
	$wikiForumID = COption::GetOptionString('wiki', 'socnet_forum_id', false, $default_site_id);
	if (intval($wikiForumID) > 0)
	{
		COption::SetOptionString("wiki", "socnet_forum_id", $wikiForumID, false, WIZARD_SITE_ID);
		COption::SetOptionString("wiki", "socnet_use_review", "Y", false, WIZARD_SITE_ID);
		COption::SetOptionString("wiki", "socnet_use_captcha", "Y", false, WIZARD_SITE_ID);
		COption::SetOptionString("wiki", "socnet_message_per_page", 10, false, WIZARD_SITE_ID);
	}
}

$WikiSocnetCommentsForumID = 0;
$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => "GROUPS_AND_USERS_TASKS_COMMENTS"));
if ($arRes = $dbRes->Fetch())
	$TasksSocnetCommentsForumID = $arRes["ID"];
?>