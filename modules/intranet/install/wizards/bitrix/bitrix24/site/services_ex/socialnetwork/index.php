<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
	return;

$APPLICATION->SetGroupRight("socialnetwork", WIZARD_EXTRANET_ADMIN_GROUP, "W");
$APPLICATION->SetGroupRight("socialnetwork", WIZARD_EXTRANET_CREATE_WG_GROUP, "K");

COption::SetOptionString("socialnetwork", "allow_frields", "N", false, WIZARD_SITE_ID, false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "subject_path_template", WIZARD_SITE_DIR."workgroups/group/search/#subject_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "group_path_template", WIZARD_SITE_DIR."workgroups/group/#group_id#/", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "messages_path", WIZARD_SITE_DIR."contacts/personal/messages/", false, WIZARD_SITE_ID);
COption::SetOptionString("socialnetwork", "default_photo_operation_write_group", "K", false, WIZARD_SITE_ID);

if(!class_exists('CUserOptions'))
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

$sOptions = 'a:1:{s:7:"GADGETS";a:9:{s:18:"SONET_USER_LINKS@1";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"SONET_USER_GROUPS@2";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_HEAD@3";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:4;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:19:"SONET_USER_HONOUR@4";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:5;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:20:"SONET_USER_ABSENCE@5";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:6;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_DESC@6";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:7:"TASKS@7";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:13:"SONET_FORUM@8";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:2;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}s:12:"SONET_BLOG@9";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:3;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
CUserOptions::SetOption("intranet", "~gadgets_sonet_user_extranet", $arOptions, false, 0);

$sOptions = 'a:1:{s:7:"GADGETS";a:2:{s:22:"SONET_USER_LINKS@23750";a:3:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:17:"SONET_USER_DESC@8";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:8:"USERDATA";N;s:4:"HIDE";s:1:"N";}}}';
$arOptions = unserialize($sOptions);
CUserOptions::SetOption("intranet", "~gadgets_sonet_group_extranet", $arOptions, false, 0);	

socialnetwork::__SetLogFilter(WIZARD_SITE_ID);

$cnt = CSocNetGroupSubject::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID), array());
if (IntVal($cnt) > 0)
	return;

$arGroupSubjects = array();
$arGroupSubjectsId = array();

for ($i = 0; $i < 4; $i++)
{
	$arGroupSubjects[$i] = array(
		"SITE_ID" => WIZARD_SITE_ID,
		"NAME" => GetMessage("SONET_GROUP_SUBJECT_".$i),
	);
	$arGroupSubjectsId[$i] = 0;
}

$errorMessage = "";

foreach ($arGroupSubjects as $ind => $arGroupSubject)
{
	$idTmp = CSocNetGroupSubject::Add($arGroupSubject);
	if ($idTmp)
	{
		$arGroupSubjectsId[$ind] = IntVal($idTmp);
	}
	else
	{
		if ($e = $GLOBALS["APPLICATION"]->GetException())
			$errorMessage .= $e->GetString();
	}
}

?>