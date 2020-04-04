<?
$arClasses = array(
	"CExtranet" => "classes/general/extranet.php",
	"CUsersInMyGroupsCache" => "classes/general/extranet.php",
//	"CExtranetWizardServices" => "classes/general/wizard_utils.php",
	"extranet" => "install/index.php",
);
CModule::AddAutoloadClasses("extranet", $arClasses);

global $obUsersCache;
$obUsersCache = new CUsersInMyGroupsCache;
?>