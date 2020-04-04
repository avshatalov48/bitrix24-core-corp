<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("blog"))
	return;

$dbGroup = CBlogGroup::GetList(array("ID" => "ASC"), array("SITE_ID" => WIZARD_SITE_ID));
if($arGroup = $dbGroup->Fetch())
{
	if (WIZARD_B24_TO_CP)
	{
		$groupID = $arGroup["ID"];

		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", Array("BLOG_GROUP_ID" => $groupID));
		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("BLOG_GROUP_ID" => $groupID));
		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("BLOG_GROUP_ID" => $groupID));
		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("BLOG_GROUP_ID" => $groupID));

	}
	return;
}

CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."contacts/personal/user/#user_id#/blog/", "TYPE" => "B"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."contacts/personal/user/#user_id#/blog/#post_id#/", "TYPE" => "P"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."contacts/personal/user/#user_id#/", "TYPE" => "U"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."workgroups/group/#group_id#/blog/", "TYPE" => "G"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."workgroups/group/#group_id#/blog/#post_id#/", "TYPE" => "H"));

$groupID = CBlogGroup::Add(Array("SITE_ID" => WIZARD_SITE_ID, "NAME" => GetMessage("BLOG_SOCNET_GROUP_EXTRANET_NAME")));

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", Array("BLOG_GROUP_ID" => $groupID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("BLOG_GROUP_ID" => $groupID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("BLOG_GROUP_ID" => $groupID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("BLOG_GROUP_ID" => $groupID));
?>