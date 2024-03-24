<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage mobileapp
 * @copyright 2001-2014 Bitrix
 */

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 */

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
CModule::IncludeModule("mobileapp");
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
$APPLICATION->SetTitle(GetMessage("MOBILEAPP_APP_DESIGNER_TITLE"));

if (!$USER->isAdmin())
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");


$action = $_REQUEST["action"];
if($action)
{
	switch ($action)
	{
		case "get_status":
			// �������� ������ ������ �� �����
			break;
		case "push_info":
			//����� ���������� �� ������ � ��������� �����
			/**
			 * �������� ����
			 * LICENSE_KEY
			 * DEV_ACCESS
			 * PLATFORMS
			 * CONTACT_EMAIL
			 */
			break;
		case "post_comment":
			/*
			//���� ��������, ��������� ���� �� �������
			//����
			*/
			break;
	}

	return;
}

CUtil::InitJSCore(Array('ajax', 'window', "popup"));

$tabs= array(
	array(
		"DIV" => "main_params",
		"TAB" => "��������",
		"TITLE" => "��������� �������� ����������",
	),
	array(
		"DIV" => "images",
		"TAB" => "�����������",
		"TITLE" => "�����������",
	),
	array(
		"DIV" => "dev_access",
		"TAB" => "������",
		"TITLE" => "������",
	)
);


$tabControl = new CAdminForm("AppEditForm", $tabs);
$tabControl->Begin();

//Main Tab start
$tabControl->BeginNextFormTab();
$tabControl->AddEditField("NAME", "�������� ����������", true, array("size" => 30, "maxlength" => 255));
$tabControl->AddEditField("SHORT_NAME", "������� ����������", true, array("size" => 30, "maxlength" => 12));
$tabControl->AddEditField("APP_FOLDER", "����� ����������", true, array("size" => 30, "maxlength" => 255));
$tabControl->AddEditField("APP_FOLDER", "����� ����������", true, array("size" => 30, "maxlength" => 255));
$tabControl->AddDropDownField("PLATFORM_LIST", "���������", true,array(
	"both"=>"Android � iOS",
	"android"=>"Android",
	"ios"=>"iOS"
));

$tabControl->AddCheckBoxField("SELF_PUBLISH","���� ����������� ���",false,false,array());
$tabControl->AddTextField("DESC","��������","",array("rows"=>10, "cols"=>55),true);
$tabControl->AddTextField("INFO", "�������������� ����������", "",array("rows" => 10, "cols" => 55),false);


//Image Tab start
$tabControl->BeginNextFormTab();
$platforms = \Bitrix\MobileApp\AppTable::getSupportedPlatforms();

foreach($platforms as $platform)
{
	$tabControl->AddSection("res_" . $platform, $platform, array(), false);
	$resources = \Bitrix\MobileApp\AppResource::get($platform);

	foreach ($resources as $resGroupName => $resGroup)
	{
		if(!empty($resGroup))
		{
			foreach ($resGroup as $res)
			{
				$iconName = str_replace("#size#", $res["width"] . "x" . $res["height"], $res["name"]);
				$tabControl->AddFileField($platform . "_ICON_" . $res["width"], $iconName, array(), false);
			}
		}
	}
}

//Dev Access Tab start
$tabControl->BeginNextFormTab();
$tabControl->AddSection("google", "������� ������������ Google Play", array(), false);
$tabControl->AddEditField("login_google", "�����: ", true, array("size" => 30, "maxlength" => 255));
$tabControl->AddEditField("password_google", "������: ", true, array("size" => 30, "maxlength" => 255));

$tabControl->AddSection("apple", "������� ������������ Apple Developer Center", array(), false);

$tabControl->AddEditField("login_apple", "�����: ", true, array("size" => 30, "maxlength" => 255));
$tabControl->AddEditField("password_apple", "������: ", true, array("size" => 30, "maxlength" => 255));


$tabControl->ShowTabButtons();
$tabControl->Show();
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php"); ?>


