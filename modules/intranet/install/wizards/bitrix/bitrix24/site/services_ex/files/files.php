<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!WIZARD_IS_INSTALLED)	
	$APPLICATION->SetFileAccessPermission(
		WIZARD_SITE_DIR."confirm/", 
		array("2" => "R")
	);
?>