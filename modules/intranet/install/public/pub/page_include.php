<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!$USER->isAuthorized() && CModule::includeModule('mail'))
	Bitrix\Mail\User::login();

$hasAccess = false;
if ($USER->isAuthorized())
{
	$hasAccess = true;

	if (CModule::includeModule('extranet') && !CExtranet::isIntranetUser())
		$hasAccess = false;

	if (isModuleInstalled('mail') || isModuleInstalled('socialservices'))
	{
		$userData = Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'EXTERNAL_AUTH_ID'),
			'filter' => array(
				'=ID' => $USER->getId()
			)
		))->fetch();

		if (isModuleInstalled('mail') && $userData['EXTERNAL_AUTH_ID'] == 'email')
			$hasAccess = true;

		if (isModuleInstalled('socialservices') && $userData['EXTERNAL_AUTH_ID'] == 'replica')
			$hasAccess = false;

		if (isModuleInstalled('im') && $userData['EXTERNAL_AUTH_ID'] == 'bot')
			$hasAccess = false;

		if (isModuleInstalled('imconnector') && $userData['EXTERNAL_AUTH_ID'] == 'imconnector')
			$hasAccess = true;
	}
}
