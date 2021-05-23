<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	"PARAMETERS" => array(
		"PATH_TO_PROFILE" => array(
			"PARENT" => "BASE",
			"NAME" => getMessage("INTRANET_USER_PROFILE_URL"),
			"TYPE" => "STRING"
		),
		"PATH_TO_PROFILE_SECURITY" => array(
			"PARENT" => "BASE",
			"NAME" => getMessage("INTRANET_USER_PROFILE_SECURITY_URL"),
			"TYPE" => "STRING"
		),
	),
);
?>