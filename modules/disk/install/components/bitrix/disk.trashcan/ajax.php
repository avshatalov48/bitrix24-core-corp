<?php
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;

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

class DiskTrashCanAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT          = 'DISK_TCAC_22001';
	const ERROR_COULD_NOT_COPY_OBJECT          = 'DISK_TCAC_22002';
	const ERROR_COULD_NOT_MOVE_OBJECT          = 'DISK_TCAC_22003';
	const ERROR_COULD_NOT_CREATE_FIND_EXT_LINK = 'DISK_TCAC_22004';
	const ERROR_COULD_NOT_RESTORE_OBJECT       = 'DISK_TCAC_22005';

	protected function listActions()
	{
		return array(
			'delete' => array(
				'method' => array('POST'),
			),
			'destroy' => array(
				'method' => array('POST'),
			),
			'empty' => array(
				'method' => array('POST'),
			),
			'restore' => array(
				'method' => array('POST'),
			),
			'calculate' => array(
				'method' => array('POST'),
			),
			'destroyPortion' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionDestroyPortion()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_TRASHCAN_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$startTime = time();
		$maxExecTime = 10;
		$countItems = 0;

		$items = $folder->getDescendants($securityContext, array(
			'filter' => array(
				'!=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
			'order' => array('PATH_CHILD.DEPTH_LEVEL' => 'DESC'),
			'limit' => 15,
		), SORT_DESC);

		$userId = $this->getUser()->getId();
		foreach($items as $item)
		{
			//I know this counter nice and lying
			$countItems++;
			/** @var File|Folder $item */
			if(!$item->canDelete($item->getStorage()->getCurrentUserSecurityContext()))
			{
				continue;
			}
			if($item instanceof Folder)
			{
				$item->deleteTree($userId);
			}
			else
			{
				$item->delete($userId);
			}

			if (time() - $startTime > $maxExecTime)
			{
				$this->sendJsonSuccessResponse(array(
					'countItems' => $countItems,
					'timeLimit' => true,
				));
			}
		}
		unset($item);

		$this->sendJsonSuccessResponse(array('countItems' => $countItems));
	}


	protected function processActionCalculate()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_TRASHCAN_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$countQuery = new Bitrix\Main\Entity\Query(FolderTable::getEntity());
		$totalCount = $countQuery
			->setFilter(array(
				'PATH_CHILD.PARENT_ID' => $folder->getId(),
				'!PATH_CHILD.OBJECT_ID' => $folder->getId(),
				'!=DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
				'=RIGHTS_CHECK' => true,
			))
			->registerRuntimeField('RIGHTS_CHECK', new ExpressionField('RIGHTS_CHECK',
				'CASE WHEN ' . $securityContext->getSqlExpressionForList('%1$s', '%2$s') . ' THEN 1 ELSE 0 END', array('ID', 'CREATED_BY'))
			)
			->addSelect(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'))
			->setLimit(null)->setOffset(null)->exec()->fetch()
		;

		$this->sendJsonSuccessResponse(array('countItems' => $totalCount['CNT']));
	}

	protected function processActionRestore()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_TRASHCAN_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		if(!$object->canRestore($object->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$object->restore($this->getUser()->getId()))
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_TRASHCAN_ERROR_COULD_NOT_RESTORE_OBJECT'), self::ERROR_COULD_NOT_RESTORE_OBJECT));
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionDestroy()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_TRASHCAN_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		if(!$object->canDelete($object->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if($object instanceof Folder)
		{
			if(!$object->deleteTree($this->getUser()->getId()))
			{
				$this->errorCollection->add($object->getErrors());
				$this->sendJsonErrorResponse();
			}
			$this->sendJsonSuccessResponse(array(
				'message' => Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FOLDER_DELETED'),
			));
		}

		if(!$object->delete($this->getUser()->getId()))
		{
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FILE_DELETED'),
		));
	}
}
$controller = new DiskTrashCanAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;