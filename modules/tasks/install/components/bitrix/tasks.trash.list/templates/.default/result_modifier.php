<?php
if ($arParams['SET_TITLE'] != 'N')
{
	//region TITLE
	$sTitle = $sTitleShort = GetMessage("TASKS_TRASH_TITLE");
	$APPLICATION->SetPageProperty("title", $sTitle);
	$APPLICATION->SetTitle($sTitleShort);
	//endregion TITLE
}