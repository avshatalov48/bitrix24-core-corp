<?php

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk'))
{
	return;
}

class DiskBitrix24DiskController extends \Bitrix\Disk\Internals\Controller
{
	protected function listActions()
	{
		return array(
			'default' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionDefault()
	{
		if($this->request->getPost('installDisk'))
		{
			\Bitrix\Disk\Desktop::setDesktopDiskInstalled();
			$this->sendJsonSuccessResponse();
		}
		if($this->request->getPost('uninstallDisk'))
		{
			\Bitrix\Disk\Desktop::setDesktopDiskUninstalled();
			$this->sendJsonSuccessResponse();
		}
		if($this->request->getPost('reInstallDisk'))
		{
			\CUserOptions::setOption('disk', 'DesktopDiskReInstall', true, false, $this->getUser()->getId());
			\Bitrix\Disk\Desktop::setDesktopDiskInstalled();

			$this->sendJsonSuccessResponse();
		}
	}

}
$action = empty($_GET['action'])? 'default' : $_GET['action'];
$controller = new DiskBitrix24DiskController();
$controller
	->setActionName($action)
	->exec()
;

