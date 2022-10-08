<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if ($arParams['SET_TITLE'] != 'N')
{
	//region TITLE
	$sTitle = $sTitleShort = GetMessage("TASKS_RECYCLEBIN_TITLE");
	$APPLICATION->SetPageProperty("title", $sTitle);
	$APPLICATION->SetTitle($sTitleShort);
	//endregion TITLE
}