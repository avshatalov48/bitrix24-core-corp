<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('webdav'))
{
	return;
}
global $USER, $APPLICATION;
if (!$USER->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}
CUtil::JSPostUnescape();

if($_POST['installDisk'])
{
	CWebDavTools::setDesktopDiskInstalled();
	if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
	{
		\Bitrix\Disk\Desktop::setDesktopDiskInstalled();
	}
	CWebDavTools::sendJsonResponse(array('status' => 'success'));
}
if($_POST['uninstallDisk'])
{
	CWebDavTools::setDesktopDiskUninstalled();
	if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
	{
		\Bitrix\Disk\Desktop::setDesktopDiskUninstalled();
	}
	CWebDavTools::sendJsonResponse(array('status' => 'success'));
}

if($_POST['reInstallDisk'])
{
	CWebDavTools::setDesktopDiskUninstalled();
	if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'))
	{
		\CUserOptions::setOption('disk', 'DesktopDiskReInstall', true, false, $USER->getId());
		\Bitrix\Disk\Desktop::setDesktopDiskInstalled();
	}
	CWebDavTools::sendJsonResponse(array('status' => 'success'));
}

