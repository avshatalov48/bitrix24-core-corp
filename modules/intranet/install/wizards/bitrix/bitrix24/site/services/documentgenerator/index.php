<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

if(!\Bitrix\Main\Loader::includeModule('documentgenerator'))
{
	return;
}

/**
 * @see \Bitrix\DocumentGenerator\Driver::installDefaultRoles()
 */
CAgent::AddAgent('\Bitrix\DocumentGenerator\Driver::installDefaultRoles();', 'documentgenerator', "N", 150, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+150, "FULL"));