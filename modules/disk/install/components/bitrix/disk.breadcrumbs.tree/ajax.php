<?php
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define("NO_KEEP_STATISTIC", true);
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

class DiskBreadcrumbsTreeAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT = 'DISK_BTAC_22001';
	const ERROR_COULD_NOT_FIND_FOLDER = 'DISK_BTAC_22002';
	const ERROR_COULD_NOT_MOVE_OBJECT = 'DISK_BTAC_22003';

	protected function listActions()
	{
		return array(
			'showSubFoldersToAdd' => array(
				'method' => array('POST'),
				'name' => 'showSubFolders',
			),
			'showSubFolders' => array(
				'method' => array('POST'),
				'name' => 'showSubFolders',
			),
			'showManySubFolders' => array(
				'method' => array('POST'),
			),
			'moveTo' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionMoveTo()
	{
		if(!$this->checkRequiredPostParams(array('objectId', 'targetObjectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_BREADCRUMBS_TREE_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		/** @var \Bitrix\Disk\Folder $targetObject */
		$targetObject = Folder::loadById((int)$this->request->getPost('targetObjectId'), array('STORAGE'));
		if(!$targetObject)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_BREADCRUMBS_TREE_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		if(!$object->canMove($securityContext, $targetObject))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$object->moveTo($targetObject, $this->getUser()->getId(), true))
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_BREADCRUMBS_TREE_ERROR_COULD_NOT_MOVE_OBJECT'), self::ERROR_COULD_NOT_MOVE_OBJECT));
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array(
			'id' => $object->getId(),
			'name' => $object->getName(),
		));
	}

	protected function processActionShowManySubFolders()
	{
		if(!$this->checkRequiredPostParams(array('objectIds')))
		{
			$this->sendJsonErrorResponse();
		}

		$objectIds = $this->request->getPost('objectIds');
		if(!is_array($objectIds))
		{
			$this->sendJsonErrorResponse();
		}

		$response = array();
		foreach($objectIds as $objectId)
		{
			$response[$objectId] = $this->getSubFolders((int)$objectId, true);
		}
		unset($objectId);

		$this->sendJsonSuccessResponse(array(
			'items' => $response,
		));
	}

	protected function processActionShowSubFolders()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'items' => $this->getSubFolders((int)$this->request->getPost('objectId')),
		));
	}

	/**
	 * @param      $targetFolderId
	 * @param bool $detectSubFoldersOnObject
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @return array
	 */
	protected function getSubFolders($targetFolderId, $detectSubFoldersOnObject = true)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById($targetFolderId);
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_BREADCRUMBS_TREE_ERROR_COULD_NOT_FIND_FOLDER'), self::ERROR_COULD_NOT_FIND_FOLDER));
			$this->sendJsonErrorResponse();
		}
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();

		$subFolders = array();
		$parameters = array('filter' => array('TYPE' => ObjectTable::TYPE_FOLDER));

		if($detectSubFoldersOnObject)
		{
			$parameters['select'] = array('*', 'HAS_SUBFOLDERS');
		}

		foreach($folder->getChildren($securityContext, $parameters) as $subFolder)
		{
			/** @var Folder $subFolder */
			$subFolders[] = array(
				'id' => $subFolder->getId(),
				'name' => $subFolder->getName(),
				'isLink' => $subFolder->isLink(),
				'hasSubFolders' => $subFolder->hasSubFolders(),
			);
		}
		unset($subFolder);
		\Bitrix\Main\Type\Collection::sortByColumn($subFolders, 'name');

		return $subFolders;
	}
}
$controller = new DiskBreadcrumbsTreeAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;