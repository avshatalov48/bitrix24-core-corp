<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("controller"))
	return;

if(!ControllerIsSharedMode())
	return false;

$arComponentDescription = array(
	"NAME" => GetMessage('CSA_DESC_NAME'),
	"DESCRIPTION" => GetMessage('CSA_DESC_DESCRIPTION'),
//	"ICON" => "/images/profile.gif",
	"PATH" => array(
		"ID" => "service",
		"CHILD" => array(
			"ID" => "controller",
			"NAME" => GetMessage('CSA_DESC_PATH_CHILD_NAME'),
		)
	),
);
?>
