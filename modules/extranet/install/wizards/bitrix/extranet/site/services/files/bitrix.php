<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

CopyDirFiles(
	WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/bitrix/",
	WIZARD_SITE_PATH."/bitrix/",
	$rewrite = false, 
	$recursive = true
);

?>
