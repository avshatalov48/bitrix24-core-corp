<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();


if(!CModule::IncludeModule("blog"))
	return;

$dbGroup = CBlogGroup::GetList(array("ID" => "ASC"), array("SITE_ID" => WIZARD_SITE_ID));
if($dbGroup->Fetch())
	return;

CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."contacts/personal/user/#user_id#/blog/", "TYPE" => "B"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."contacts/personal/user/#user_id#/blog/#post_id#/", "TYPE" => "P"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."contacts/personal/user/#user_id#/", "TYPE" => "U"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."workgroups/group/#group_id#/blog/", "TYPE" => "G"));
CBlogSitePath::Add(Array("SITE_ID" => WIZARD_SITE_ID, "PATH" => WIZARD_SITE_DIR."workgroups/group/#group_id#/blog/#post_id#/", "TYPE" => "H"));

$groupID = CBlogGroup::Add(Array("SITE_ID" => WIZARD_SITE_ID, "NAME" => GetMessage("BLOG_SOCNET_GROUP_EXTRANET_NAME")));
?>