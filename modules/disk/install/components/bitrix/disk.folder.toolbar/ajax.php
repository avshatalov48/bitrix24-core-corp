<?php
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Ui;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define("NOT_CHECK_PERMISSIONS", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskFolderToolbarAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT = 'DISK_FTAC_22001';

	protected function listActions()
	{
		return array(
			'addFolder' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionAddFolder()
	{
		if(!$this->checkRequiredPostParams(array(
			'name', 'targetFolderId'
		)))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getPost('targetFolderId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error(
				Loc::getMessage('DISK_FOLDER_TOOLBAR_ERROR_COULD_NOT_FIND_OBJECT'),
				self::ERROR_COULD_NOT_FIND_OBJECT
			));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$name = $this->request->getPost('name');
		$subFolderModel = $folder->addSubFolder(array(
			'NAME' => Ui\Text::correctFolderName($name),
			'CREATED_BY' => $this->getUser()->getId()
		));
		if($subFolderModel === null)
		{
			$this->errorCollection->add($folder->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array(
			'folder' => array(
				'id' => $subFolderModel->getId(),
			),
		));
	}
}
$controller = new DiskFolderToolbarAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;