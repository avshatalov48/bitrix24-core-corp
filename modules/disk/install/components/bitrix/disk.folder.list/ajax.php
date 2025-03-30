<?php

use Bitrix\Disk\Analytics\DiskAnalytics;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\SessionTerminationServiceFactory;
use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\File;
use Bitrix\Disk\FileLink;
use Bitrix\Disk\Folder;
use Bitrix\Disk\FolderLink;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\Storage;
use Bitrix\Disk\User;
use Bitrix\Disk\Ui;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define("NOT_CHECK_PERMISSIONS", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskFolderListAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT          = 'DISK_FLAC_22001';
	const ERROR_COULD_NOT_COPY_OBJECT          = 'DISK_FLAC_22002';
	const ERROR_COULD_NOT_MOVE_OBJECT          = 'DISK_FLAC_22003';
	const ERROR_COULD_NOT_CREATE_FIND_EXT_LINK = 'DISK_FLAC_22004';
	const ERROR_DISK_QUOTA                     = 'DISK_FLAC_22005';

	private ?array $prevRightsOnStorage = null;

	protected function listActions()
	{
		return array(
			'createFolderWithSharing' => array(
				'method' => array('POST'),
			),
			'showCreateFolderWithSharingInCommon' => array(
				'method' => array('POST'),
			),
			'showRightsOnStorageDetail' => array(
				'method' => array('POST'),
			),
			'showRightsOnObjectDetail' => array(
				'method' => array('POST'),
			),
			'showSettingsOnBizproc' => array(
				'method' => array('POST'),
			),
			'saveSettingsOnBizproc' => array(
				'method' => array('POST'),
			),
			'saveRightsOnStorage' => array(
				'method' => array('POST'),
			),
			'saveRightsOnObject' => array(
				'method' => array('POST'),
			),
			'showRightsDetail' => array(
				'method' => array('POST'),
			),
			'showSharingDetail' => array(
				'method' => array('POST'),
			),
			'showSharingDetailChangeRights' => array(
				'method' => array('POST'),
			),
			'showSharingDetailAppendSharing' => array(
				'method' => array('POST'),
				'name' => 'showSharingDetailChangeRights'
			),
			'changeSharingAndRights' => array(
				'method' => array('POST'),
			),
			'appendSharing' => array(
				'method' => array('POST'),
			),
			'copyTo' => array(
				'method' => array('POST'),
			),
			'moveTo' => array(
				'method' => array('POST'),
			),
			'showSubFoldersToAdd' => array(
				'method' => array('POST'),
			),
			'showSubFolders' => array(
				'method' => array('POST'),
				'name' => 'showSubFoldersToAdd',
			),
			'showShareInfoSmallView' => array(
				'method' => array('POST'),
			),
			'connectToUserStorage' => array(
				'method' => array('POST'),
			),
			'disableExternalLink' => array(
				'method' => array('POST'),
			),
			'getExternalLink' => array(
				'method' => array('POST'),
			),
			'getDetailSettingsExternalLink' => array(
				'method' => array('POST'),
			),
			'getDetailSettingsExternalLinkForceCreate' => array(
				'method' => array('POST'),
			),
			'saveSettingsExternalLink' => array(
				'method' => array('POST'),
			),
			'generateExternalLink' => array(
				'method' => array('POST'),
			),
			'markDelete' => array(
				'method' => array('POST'),
			),
			'delete' => array(
				'method' => array('POST'),
			),
			'detach' => array(
				'method' => array('POST'),
				'name' => 'markDelete',
			),
			'validateParameterAutoloadBizProc' => array(
				'method' => array('POST'),
			),
			'calculateFileSizeAndCount' => array(
				'method' => array('POST'),
			),
			'getUrlToDownloadArchive' => array(
				'method' => array('POST'),
			),
			'lock' => array(
				'method' => array('POST'),
			),
			'unlock' => array(
				'method' => array('POST'),
			),
			'addFolder' => array(
				'method' => array('POST'),
			),
			'showSymlinks' => array(
				'method' => array('POST'),
			),
		);
	}

	/**
	 * Action only for Common docs.
	 * We don't check rights on rootObject for changeRights if user has "update" operation.
	 */
	protected function processActionShowCreateFolderWithSharingInCommon($storageId)
	{
		$storage = Storage::loadById((int)$storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!($storage->getProxyType() instanceof Bitrix\Disk\ProxyType\Common) || !$storage->getRootObject()->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$rightsByAccessCode = array();
		foreach($rightsManager->getAllListNormalizeRights($storage->getRootObject()) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));
		//some access code can be deleted. Clean
		$rightsByAccessCode = array_intersect_assoc($rightsByAccessCode, $names);
		$destination = Ui\Destination::getRightsDestination($this->getUser()->getId(), array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse(array(
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
			'tasks' => $rightsManager->getTasks(),
			'destination' => array(
				'items' => array(
					'users' => $destination['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['SONETGROUPS'],
					'department' => $destination['DEPARTMENT'],
					'departmentRelation' => $destination['DEPARTMENT_RELATION'],
				),
				'itemsLast' => array(
					'users' => $destination['LAST']['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['LAST']['SONETGROUPS'],
					'department' => $destination['LAST']['DEPARTMENT'],
				),
				'itemsSelected' => array(),
			),
		));
	}

	/**
	 * Action only for Common docs.
	 * We don't check rights on rootObject for changeRights if user has "update" operation.
	 */
	protected function processActionCreateFolderWithSharing($storageId, $name)
	{
		//todo refactor actions. And move logic in rights manager if needed
		$storage = Storage::loadById((int)$storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		//todo refactor this code. with action ShowCreateFolderWithSharingInCommon
		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!($storage->getProxyType() instanceof Bitrix\Disk\ProxyType\Common) || !$storage->getRootObject()->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$entityToNewShared = $this->request->getPost('entityToNewShared');
		//here we have only rights what we decline. There is not opportunity to modify rights. Only decline.
		$storageNewRights = $this->request->getPost('storageNewRights');
		if(!$storageNewRights || !is_array($storageNewRights))
		{
			$storageNewRights = array();
		}

		$newRights = array();
		foreach($rightsManager->getAllListNormalizeRights($storage->getRootObject()) as $rightOnObject)
		{
			//skip negative rights
			if (!empty($rightOnObject['NEGATIVE']))
			{
				continue;
			}

			//it is the same right what already was
			if (isset($storageNewRights[$rightOnObject['ACCESS_CODE']]))
			{
				continue;
			}

			$newRights[] = array(
				'ACCESS_CODE' => $rightOnObject['ACCESS_CODE'],
				'TASK_ID' => $rightOnObject['TASK_ID'],
				'NEGATIVE' => 1,
			);
		}

		if($newRights)
		{
			$newRights[] = array(
				'ACCESS_CODE' => 'IU' . $this->getUser()->getId(),
				'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
			);
		}

		$newFolder = $storage->addFolder(
			array(
				'NAME' => Ui\Text::correctFolderName($name),
				'CREATED_BY' => $this->getUser()->getId()
			),
			$newRights
		);
		if($newFolder === null)
		{
			$this->errorCollection->add($storage->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!empty($entityToNewShared) && is_array($entityToNewShared))
		{
			$newExtendedRightsReformat = array();
			foreach($entityToNewShared as $entityId => $right)
			{
				switch($right['right'])
				{
					case $rightsManager::TASK_READ:
					case $rightsManager::TASK_ADD:
					case $rightsManager::TASK_EDIT:
					case $rightsManager::TASK_FULL:
						$newExtendedRightsReformat[$entityId] = $right['right'];
						break;
				}
			}

			if($newExtendedRightsReformat)
			{
				$sharings = Sharing::addToManyEntities(array(
					'FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
					'REAL_OBJECT' => $newFolder,
					'CREATED_BY' => $this->getUser()->getId(),
					'CAN_FORWARD' => false,
				), $newExtendedRightsReformat, $this->errorCollection);

				if (is_array($sharings))
				{
					$destinationCodes = [];
					foreach($sharings as $key => $sharing)
					{
						$destinationCodes[] = $sharing->getToEntity();
					}

					if (!empty($destinationCodes))
					{
						\Bitrix\Main\FinderDestTable::merge([
							"CONTEXT" => "DISK_SHARE",
							"CODE" => \Bitrix\Main\FinderDestTable::convertRights(array_unique($destinationCodes))
						]);
					}
				}
			}
		}

		$this->sendJsonSuccessResponse(array(
			'folder' => array(
				'id' => $newFolder->getId(),
			),
		));
	}

	protected function processActionShowRightsOnStorageDetail($storageId)
	{
		if (!Bitrix24Manager::isFeatureEnabled('disk_folder_sharing'))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$storage = Storage::loadById((int)$storageId, ['ROOT_OBJECT']);
		if (!$storage)
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'),
				self::ERROR_COULD_NOT_FIND_OBJECT
			);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		$proxyType = $storage->getProxyType();
		if (!$storage->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$readOnlyAccessCodes = [];
		if ($proxyType instanceof Bitrix\Disk\ProxyType\User && !User::isCurrentUserAdmin())
		{
			$readOnlyAccessCodes['IU' . $storage->getEntityId()] = true;
			$readOnlyAccessCodes['U' . $storage->getEntityId()] = true;
		}
		elseif ($proxyType instanceof Bitrix\Disk\ProxyType\Group)
		{
			$readOnlyAccessCodes['SG' . $storage->getEntityId() . '_A'] = true;
		}

		$rightsByAccessCode = [];
		foreach ($rightsManager->getAllListNormalizeRights($storage->getRootObject()) as $rightOnObject)
		{
			if (empty($rightOnObject['NEGATIVE']))
			{
				if (isset($readOnlyAccessCodes[$rightOnObject['ACCESS_CODE']]))
				{
					$rightOnObject['READ_ONLY'] = true;
				}
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));

		$systemFolderNames = [];
		if ($proxyType instanceof ProxyType\Common)
		{
			foreach ($proxyType->listPseudoSystemFolders($securityContext) as $systemFolder)
			{
				$systemFolderNames[] = $systemFolder->getName();
			}
		}

		$this->sendJsonSuccessResponse([
			'storage' => [
				'id' => $storage->getId(),
				'name' => $proxyType->getTitleForCurrentUser(),
			],
			'systemFolders' => [
				'show' => !empty($systemFolderNames),
				'names' => $systemFolderNames,
			],
			'showExtendedRights' => $storage->isEnabledShowExtendedRights(),
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
			'tasks' => $rightsManager->getTasks(),
		]);
	}

	protected function processActionShowRightsOnObjectDetail($objectId)
	{
		/** @var File|Folder $object */
		$object = BaseObject::loadById((int)$objectId);
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}
		$storage = $object->getStorage();
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$rightsByAccessCode = array();
		$allListNormalizeRights = $rightsManager->getAllListNormalizeRights($object);

		$negativeRights = array();
		foreach($allListNormalizeRights as $i => $rightOnObject)
		{
			if(!empty($rightOnObject['NEGATIVE']))
			{
				$negativeRights[$rightOnObject['ACCESS_CODE'] . '-' . $rightOnObject['TASK_ID']] = true;
				unset($allListNormalizeRights[$i]);
			}
		}

		foreach($allListNormalizeRights as $rightOnObject)
		{
			if(
				empty($rightOnObject['NEGATIVE']) &&
				empty($negativeRights[$rightOnObject['ACCESS_CODE'] . '-' . $rightOnObject['TASK_ID']])
			)
			{
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse([
			'object' => [
				'name' => $object->getName(),
			],
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
			'tasks' => $rightsManager->getTasks(),
		]);
	}

	protected function processActionShowSettingsOnBizproc($storageId)
	{
		$storage = Storage::loadById((int)$storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'statusBizProc' => (bool)$storage->isEnabledBizProc(),
		));
	}

	protected function processActionSaveSettingsOnBizproc($storageId)
	{
		$storage = Storage::loadById((int)$storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->canChangeSettings($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$status = (int)$this->request->getPost('activationBizproc')?
			$storage->enableBizProc() : $storage->disableBizProc();
		if(!$status)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_SAVE'));
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionSaveRightsOnStorage($storageId)
	{
		if (!Bitrix24Manager::isFeatureEnabled('disk_folder_sharing'))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$storage = Storage::loadById((int)$storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$showExtendedRights = (bool)$this->request->getPost('showExtendedRights');
		if($storage->isEnabledShowExtendedRights() != $showExtendedRights)
		{
			$showExtendedRights?
				$storage->enableShowExtendedRights() : $storage->disableShowExtendedRights();
		}

		if(!$this->request->getPost('isChangedRights'))
		{
			$this->sendJsonSuccessResponse();
		}

		$storageNewRights = $this->request->getPost('storageNewRights');
		if(!empty($storageNewRights) && is_array($storageNewRights))
		{
			$newRights = array();
			foreach($storageNewRights as $accessCode => $right)
			{
				if(!empty($right['right']['id']))
				{
					$newRights[] = array(
						'ACCESS_CODE' => $accessCode,
						'TASK_ID' => $right['right']['id'],
					);
				}
			}

			if(empty($newRights))
			{
				$this->sendJsonErrorResponse();
			}

			if (($storage->getProxyType() instanceof ProxyType\Common) && !$this->request->getPost('setRightsOnPseudoSystemFolders'))
			{
				$this->setRightsOnCommonStorage($newRights, $storage, $securityContext);
				$this->sendJsonSuccessResponse();
			}

			if($rightsManager->set($storage->getRootObject(), $newRights))
			{
				$this->sendJsonSuccessResponse();
			}
		}

		$this->errorCollection->addFromEntity($rightsManager);

		$this->sendJsonErrorResponse();
	}

	private function setRightsOnCommonStorage(array $newRights, Storage $storage, SecurityContext $securityContext): void
	{
		if (!($storage->getProxyType() instanceof ProxyType\Common))
		{
			return;
		}

		$newSimpleRights = [];
		foreach ($newRights as $right)
		{
			if ($right['NEGATIVE'])
			{
				continue;
			}

			$newSimpleRights[] = [
				'ACCESS_CODE' => $right['ACCESS_CODE'],
				'OBJECT_ID' => $storage->getRootObjectId(),
			];
		}

		SimpleRightTable::deleteBatch(['OBJECT_ID' => $storage->getRootObjectId()]);
		SimpleRightTable::insertBatch($newSimpleRights);

		$rightsManager = \Bitrix\Disk\Driver::getInstance()->getRightsManager();
		$rootItems = $storage->getChildren($securityContext);

		$pseudoSystemFoldersId = [];
		foreach ($rootItems as $item)
		{
			if (
				in_array($item->getCode(), ProxyType\Common::PSEUDO_SYSTEM_FOLDER_CODE, true)
				|| in_array($item->getXmlId(), ProxyType\Common::PSEUDO_SYSTEM_FOLDER_XML_ID, true))
			{
				$pseudoSystemFoldersId[] = $item->getId();
				continue;
			}

			$rightsManager->append($item, $newRights);
		}

		$rightsOnRootObject = [];
		$pseudoSystemRights = [];
		foreach ($newRights as $newRight)
		{
			$rightsOnRootObject[] = [
				'OBJECT_ID' => $storage->getRootObjectId(),
				'TASK_ID' => $newRight['TASK_ID'],
				'ACCESS_CODE' => $newRight['ACCESS_CODE'],
				'DOMAIN' => $newRight['DOMAIN'] ?? null,
				'NEGATIVE' => $newRight['NEGATIVE'] ?? 0,
			];

			foreach ($pseudoSystemFoldersId as $folderId)
			{
				$pseudoSystemRights[] = [
					'OBJECT_ID' => $folderId,
					'TASK_ID' => $newRight['TASK_ID'],
					'ACCESS_CODE' => $newRight['ACCESS_CODE'],
					'DOMAIN' => null,
					'NEGATIVE' => 1,
				];
			}

		}
		RightTable::deleteBatch(['OBJECT_ID' => $storage->getRootObjectId()]);
		RightTable::insertBatch(array_merge($rightsOnRootObject, $pseudoSystemRights));
	}

	protected function processActionSaveRightsOnObject($objectId)
	{
		/** @var File|Folder $object */
		$object = BaseObject::loadById((int)$objectId);
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}
		$storage = $object->getStorage();
		if(!$storage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$specificRights = new DataSetOfRights($rightsManager->getSpecificRights($object));
		$specificOnlyNegativeRights = $specificRights->filterNegative();

		$inheritedRights = DataSetOfRights::createByArray($rightsManager->getAllListNormalizeRights($object))
			->filterByCallback(function($right) use ($specificRights) {
				return !$specificRights->isExists(array(
					'ACCESS_CODE' => $right['ACCESS_CODE'],
					'TASK_ID' => $right['TASK_ID'],
				));
			});

		$newRights = $detachedRights = array();
		$objectNewRights = $this->request->getPost('objectNewRights');
		if(!empty($objectNewRights) && is_array($objectNewRights))
		{
			foreach($objectNewRights as $accessCode => $right)
			{
				if(empty($right['right']['id']))
				{
					continue;
				}

				$alreadySpecific = $specificRights->filterByFields(array(
					'ACCESS_CODE' => $accessCode,
					'TASK_ID' => $right['right']['id'],
				))->getFirst();
				$alreadyInherited = $inheritedRights->filterByFields(array(
					'ACCESS_CODE' => $accessCode,
					'TASK_ID' => $right['right']['id'],
				))->getFirst();

				if ($alreadyInherited)
				{
					//if there is already inherited right we don't have to make double
					continue;
				}

				//so, seems we have a new right
				$newRight = array(
					'ACCESS_CODE' => $accessCode,
					'TASK_ID' => $right['right']['id'],
				);

				if ($alreadySpecific)
				{
					//if it looks like specific, then we make copy and save domain
					$newRight['DOMAIN'] = !empty($alreadySpecific['DOMAIN'])?
						$alreadySpecific['DOMAIN'] : null;
				}
				else
				{
					//so, try to find inherited right with the same access code.
					$inheritedOnSameAccessCode = $inheritedRights->filterByFields(array(
						'ACCESS_CODE' => $accessCode,
					))->getFirst();
					if ($inheritedOnSameAccessCode)
					{
						//if we found inherited right we decline it. So in this way we overwrite it by newRight
						$specificOnlyNegativeRights[] = array(
							'ACCESS_CODE' => $accessCode,
							'TASK_ID' => $inheritedOnSameAccessCode['TASK_ID'],
							'NEGATIVE' => 1,
						);
					}
				}

				$newRights[] = $newRight;
			}
		}

		$detachedRightsFromPost = $this->request->getPost('detachedRights');
		if(!empty($detachedRightsFromPost) && is_array($detachedRightsFromPost))
		{
			$specificRights = new DataSetOfRights($newRights);
			foreach($detachedRightsFromPost as $accessCode => $right)
			{
				if(empty($right['right']['id']))
				{
					continue;
				}

				$alreadySpecific = $specificRights->isExists(array(
					'ACCESS_CODE' => $accessCode,
					'TASK_ID' => $right['right']['id'],
				));
				$alreadyInherited = $inheritedRights->isExists(array(
					'ACCESS_CODE' => $accessCode,
					'TASK_ID' => $right['right']['id'],
				));

				if($alreadySpecific || $alreadyInherited)
				{
					$detachedRights[] = array(
						'ACCESS_CODE' => $accessCode,
						'TASK_ID' => $right['right']['id'],
						'NEGATIVE' => 1,
					);
				}
			}
		}
		if (!$specificOnlyNegativeRights->isEmpty())
		{
			//we can decline only inherited rights.
			$inheritedRights = new DataSetOfRights($rightsManager->getParentsRights($object->getRealObjectId()));
			foreach($specificOnlyNegativeRights as $key => $negativeRight)
			{
				$isExists = $inheritedRights->isExists(array(
				   'ACCESS_CODE' => $negativeRight['ACCESS_CODE'],
				   'TASK_ID' => $negativeRight['TASK_ID'],
				));
				if(!$isExists)
				{
					unset($specificOnlyNegativeRights[$key]);
				}
			}
		}

		$changedDomainRights = $this->getChangedDomainRights($specificRights, $newRights);
		$newRights = $this->addDomainToNewRights($changedDomainRights, $newRights);
		if($rightsManager->set($object, array_merge($newRights, $detachedRights, $specificOnlyNegativeRights->toArray())))
		{
			$this->updateSharingTasks($changedDomainRights);

			$this->sendJsonSuccessResponse();
		}

		$this->errorCollection->addFromEntity($rightsManager);

		$this->sendJsonErrorResponse();
	}

	private function addDomainToNewRights(array $changedDomainRights, array $newRights)
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		foreach($changedDomainRights as $rights)
		{
			list($old, $new) = $rights;

			$taskName = $rightsManager->getTaskNameById($new['TASK_ID']);
			switch($taskName)
			{
				case $rightsManager::TASK_READ:
				case $rightsManager::TASK_ADD:
				case $rightsManager::TASK_EDIT:
				case $rightsManager::TASK_FULL:

					foreach($newRights as $i => $newRight)
					{
						if(
							$newRight['ACCESS_CODE'] === $new['ACCESS_CODE'] &&
							$newRight['TASK_ID'] === $new['TASK_ID']
						)
						{
							$newRights[$i]['DOMAIN'] = $old['DOMAIN'];
						}
					}

					break;
			}
		}

		return $newRights;
	}

	private function updateSharingTasks(array $changedDomainRights)
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		foreach($changedDomainRights as $rights)
		{
			list($old, $new) = $rights;

			$sharingId = $rightsManager->getIdBySharingDomain($old['DOMAIN']);
			if(!$sharingId)
			{
				continue;
			}

			$sharing = Sharing::loadById($sharingId);
			if(!$sharing)
			{
				continue;
			}
			$taskName = $rightsManager->getTaskNameById($new['TASK_ID']);
			switch($taskName)
			{
				case $rightsManager::TASK_READ:
				case $rightsManager::TASK_ADD:
				case $rightsManager::TASK_EDIT:
				case $rightsManager::TASK_FULL:
					$sharing->changeTaskName($taskName);
			}

		}
	}

	private function getChangedDomainRights($specificRights, array $newRights)
	{
		$changedDomainRights = array();
		foreach($specificRights as $right)
		{
			if(empty($right['DOMAIN']))
			{
				continue;
			}
			if(!empty($right['NEGATIVE']))
			{
				continue;
			}
			foreach($newRights as $newRight)
			{
				if($newRight['ACCESS_CODE'] !== $right['ACCESS_CODE'])
				{
					continue;
				}
				if($newRight['TASK_ID'] == $right['TASK_ID'])
				{
					break;
				}
				//will be new right on same access code and with different task.
				$changedDomainRights[] = array($right, $newRight);
			}
		}

		return $changedDomainRights;
	}

	protected function processActionShowRightsDetail($objectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$rightsByAccessCode = array();
		foreach($rightsManager->getAllListNormalizeRights($object->getRealObject()) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse(array(
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
		));
	}

	protected function processActionShowSharingDetail($objectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		//user has only read right. And he can't see on another sharing
		if(!$object->canShare($securityContext) && !$object->canChangeRights($securityContext))
		{
			/** @var User $user */
			$user = User::getById($this->getUser()->getId());
			$entityList = array(
				array(
					'entityId' => Sharing::CODE_USER . $this->getUser()->getId(),
					'name' => $user->getFormattedName(),
					'right' => $rightsManager->getPseudoMaxTaskByObjectForUser($object, $user->getId()),
					'avatar' => $user->getAvatarSrc(),
					'type' => 'users',
				)
			);
		}
		else
		{
			$entityList = $object->getMembersOfSharing();
		}

		if (!$object->getRealObject())
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'members' => $entityList,
			'owner' => $this->getOwner($object),
		));
	}

	protected function processActionShowSharingDetailChangeRights($objectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object || !$object->getRealObject())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		//user has only read right. And he can't see on another sharing
		if(!$object->canShare($securityContext) && !$object->canChangeRights($securityContext))
		{
			/** @var User $user */
			$user = User::getById($this->getUser()->getId());
			$entityList = array(
				array(
					'entityId' => Sharing::CODE_USER . $this->getUser()->getId(),
					'name' => $user->getFormattedName(),
					'right' => $rightsManager->getPseudoMaxTaskByObjectForUser($object, $user->getId()),
					'avatar' => $user->getAvatarSrc(),
					'type' => 'users',
				)
			);
		}
		else
		{
			$entityList = $object->getMembersOfSharing();
		}

		$selected = array();
		foreach($entityList as $entity)
		{
			$selected[] = $entity['entityId'];
		}
		$destination = Ui\Destination::getSocNetDestination($this->getUser()->getId(), $selected);

		$rightsManager = Driver::getInstance()->getRightsManager();
		$canOnlyShare = $object->canShare($securityContext) && !$object->canChangeRights($securityContext);
		$maxTaskName = $rightsManager::TASK_FULL;
		if($canOnlyShare)
		{
			$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($object, $this->getUser()->getId());
		}
		$owner = $this->getOwner($object);
		$owner['canOnlyShare'] = $canOnlyShare;
		$owner['maxTaskName'] = $maxTaskName;

		$this->sendJsonSuccessResponse(array(
			'members' => $entityList,
			'owner' => $owner,
			'destination' => array(
				'items' => array(
					'users' => $destination['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['SONETGROUPS'],
					'department' => $destination['DEPARTMENT'],
					'departmentRelation' => $destination['DEPARTMENT_RELATION'],
				),
				'itemsLast' => array(
					'users' => $destination['LAST']['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['LAST']['SONETGROUPS'],
					'department' => $destination['LAST']['DEPARTMENT'],
				),
				'itemsSelected' => $destination['SELECTED'],
			),
		));
	}

	protected function getOwner(BaseObject $object): array
	{
		$proxyType = $object->getRealObject()->getStorage()->getProxyType();
		if ($proxyType instanceof \Bitrix\Im\Disk\ProxyType\Im)
		{
			$createUser = $object->getRealObject()->getCreateUser();

			return [
				'name' => $createUser->getFormattedName(),
				'avatar' => $createUser->getAvatarSrc(58, 58),
				'link' => $createUser->getDetailUrl(),
			];
		}

		return [
			'name' => $proxyType->getEntityTitle(),
			'avatar' => $proxyType->getEntityImageSrc(58, 58),
			'link' => $proxyType->getEntityUrl(),
		];
	}

	protected function processActionAppendSharing($objectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		$rightsManager = Driver::getInstance()->getRightsManager();
		if(!$object->canShare($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$entityToNewShared = $this->request->getPost('entityToNewShared');
		if(!empty($entityToNewShared) && is_array($entityToNewShared))
		{
			$extendedRights = $entityToNewShared;
			$newExtendedRightsReformat = array();
			foreach($extendedRights as $entityId => $right)
			{
				if ($rightsManager->isValidTaskName($right['right']))
				{
					$newExtendedRightsReformat[$entityId] = $right['right'];
				}
			}

			//todo move this code to Object or Sharing model (reset sharing)
			$query = Sharing::getList(array(
				'select' => array('ID', 'TO_ENTITY', 'TASK_NAME'),
				'filter' => array(
					'REAL_OBJECT_ID' => $object->getRealObjectId(),
					'REAL_STORAGE_ID' => $object->getRealObject()->getStorageId(),
					'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
				),
			));
			while($sharingRow = $query->fetch())
			{
				if(isset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]))
				{
					unset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]);
				}
			}

			$needToAdd = $newExtendedRightsReformat;
			if($needToAdd)
			{
				//todo! refactor this to compare set of operations. And move this code
				$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($object, $this->getUser()->getId());
				if(!$maxTaskName)
				{
					//this user could not share object, he doesn't have access
					$this->sendJsonErrorResponse();
				}

				foreach($needToAdd as $entityId => $right)
				{
					//if upgrade - then skip
					if($rightsManager->pseudoCompareTaskName($right, $maxTaskName) > 0)
					{
						unset($needToAdd[$entityId]);
					}
				}

				if($needToAdd)
				{
					$sharings = Sharing::addToManyEntities(array(
						'FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
						'REAL_OBJECT' => $object,
						'CREATED_BY' => $this->getUser()->getId(),
						'CAN_FORWARD' => false,
					), $needToAdd, $this->errorCollection);

					if (is_array($sharings))
					{
						$destinationCodes = [];
						foreach($sharings as $key => $sharing)
						{
							$destinationCodes[] = $sharing->getToEntity();
						}

						if (!empty($destinationCodes))
						{
							\Bitrix\Main\FinderDestTable::merge([
								"CONTEXT" => "DISK_SHARE",
								"CODE" => \Bitrix\Main\FinderDestTable::convertRights(array_unique($destinationCodes))
							]);
						}
					}
				}
			}
		}
		\Bitrix\Disk\Driver::getInstance()->getTrackedObjectManager()->refresh($object);
		$this->sendJsonSuccessResponse();
	}

	protected function processActionChangeSharingAndRights($objectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		\Bitrix\Disk\Driver::getInstance()->getTrackedObjectManager()->refresh($object);

		$currentUserId = $this->getUser()->getId();
		$entityToNewShared = $this->request->getPost('entityToNewShared');
		if(!empty($entityToNewShared) && is_array($entityToNewShared))
		{
			$extendedRights = $entityToNewShared;
			$newExtendedRightsReformat = array();
			foreach($extendedRights as $entityId => $right)
			{
				switch($right['right'])
				{
					case \Bitrix\Disk\RightsManager::TASK_READ:
					case \Bitrix\Disk\RightsManager::TASK_ADD:
					case \Bitrix\Disk\RightsManager::TASK_EDIT:
					case \Bitrix\Disk\RightsManager::TASK_FULL:
						$newExtendedRightsReformat[$entityId] = $right['right'];
						break;
				}
			}
			//todo move this code to Object or Sharing model (reset sharing)
			$query = Sharing::getList(array(
				'filter' => array(
					'REAL_OBJECT_ID' => $object->getRealObjectId(),
					'REAL_STORAGE_ID' => $object->getRealObject()->getStorageId(),
					'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
					'PARENT_ID' => null,
				),
			));
			$needToOverwrite = $needToDelete = $needToAdd = $deletedOrChangedSharingItems = array();
			while($sharingRow = $query->fetch())
			{
				if(isset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]))
				{
					if($newExtendedRightsReformat[$sharingRow['TO_ENTITY']] != $sharingRow['TASK_NAME'])
					{
						$needToOverwrite[$sharingRow['TO_ENTITY']] = $sharingRow;
					}
					elseif($newExtendedRightsReformat[$sharingRow['TO_ENTITY']] == $sharingRow['TASK_NAME'])
					{
						unset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]);
					}
				}
				else
				{
					$needToDelete[$sharingRow['TO_ENTITY']] = $sharingRow;
				}
			}

			$needToAdd = array_diff_key($newExtendedRightsReformat, $needToOverwrite);
			foreach ($needToAdd as $entity => $taskName)
			{
				if (!Sharing::hasRightToKnowAboutEntity($currentUserId, $entity))
				{
					unset($needToAdd[$entity]);
				}
			}

			if($needToAdd)
			{

				$sharings = Sharing::addToManyEntities(array(
					'FROM_ENTITY' => Sharing::CODE_USER . $currentUserId,
					'REAL_OBJECT' => $object,
					'CREATED_BY' => $this->getUser()->getId(),
					'CAN_FORWARD' => false,
				), $needToAdd, $this->errorCollection);

				if (is_array($sharings))
				{
					$destinationCodes = [];
					foreach($sharings as $key => $sharing)
					{
						$destinationCodes[] = $sharing->getToEntity();
					}

					if (!empty($destinationCodes))
					{
						\Bitrix\Main\FinderDestTable::merge([
							"CONTEXT" => "DISK_SHARE",
							"CODE" => \Bitrix\Main\FinderDestTable::convertRights(array_unique($destinationCodes))
						]);
					}
				}
			}
			if($needToOverwrite)
			{
				$rightsManager = Driver::getInstance()->getRightsManager();
				foreach($needToOverwrite as $sharingRow)
				{
					$rightsManager->deleteByDomain($object->getRealObject(), $rightsManager->getSharingDomain($sharingRow['ID']));
				}

				$newRights = array();
				foreach($needToOverwrite as $sharingRow)
				{
					$sharingDomain = $rightsManager->getSharingDomain($sharingRow['ID']);
					$newRights[] = array(
						'ACCESS_CODE' => $sharingRow['TO_ENTITY'],
						'TASK_ID' => $rightsManager->getTaskIdByName($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]),
						'DOMAIN' => $sharingDomain,
					);
					//todo refactor. Move most logic to Sharing and SharingTable!
					if($sharingRow['TYPE'] == SharingTable::TYPE_TO_DEPARTMENT)
					{
						/** @var \Bitrix\Disk\Sharing $sharingModel */
						$sharingModel = Sharing::buildFromArray($sharingRow);
						$sharingModel->changeTaskName($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]);
					}
					else
					{
						$sharingUpdateResult = SharingTable::update($sharingRow['ID'], array(
							'TASK_NAME' => $newExtendedRightsReformat[$sharingRow['TO_ENTITY']],
						));

						$isSharingTypeToUser = (int)$sharingRow['TYPE'] === SharingTable::TYPE_TO_USER;
						$isChangedAccessToRead = $newExtendedRightsReformat[$sharingRow['TO_ENTITY']] === $rightsManager::TASK_READ;
						if ($isChangedAccessToRead && $isSharingTypeToUser && $sharingUpdateResult->isSuccess())
						{
							$deletedOrChangedSharingItems[] = Sharing::buildFromRow($sharingRow);
						}
					}
				}

				if($newRights)
				{
					$rightsManager->append($object->getRealObject(), $newRights);
				}
			}
			if($needToDelete)
			{
				$ids = array();
				foreach($needToDelete as $sharingRow)
				{
					$ids[] = $sharingRow['ID'];
				}

				foreach(Sharing::getModelList(array('filter' => array('ID' => $ids))) as $sharing)
				{
					if($sharing->delete($currentUserId))
					{
						$deletedOrChangedSharingItems[] = $sharing;
					}
				}
			}

			Application::getInstance()->addBackgroundJob(
				function () use ($object, $deletedOrChangedSharingItems) {
					$this->terminateAllSessionsForChangedOrDeletedSharingItems($object->getId(), $deletedOrChangedSharingItems);
				}
			);

			$this->sendJsonSuccessResponse();
		}
		else
		{
			//user delete all sharing
			$deletedSharingItems = [];
			foreach($object->getRealObject()->getSharingsAsReal() as $sharing)
			{
				if ($sharing->delete($currentUserId))
				{
					$deletedSharingItems[] = $sharing;
				}
			}

			Application::getInstance()->addBackgroundJob(
				function () use ($object, $deletedSharingItems) {
					$this->terminateAllSessionsForChangedOrDeletedSharingItems($object->getId(), $deletedSharingItems);
				}
			);

			$this->sendJsonSuccessResponse();
		}
	}

	/**
	 * @param int $objectId
	 * @param Sharing[] $sharingItems
	 * @return void
	 */
	private function terminateAllSessionsForChangedOrDeletedSharingItems(int $objectId, array $sharingItems): void
	{
		$userIds = [];
		foreach ($sharingItems as $deletedSharing)
		{
			if ($deletedSharing->isToUser())
			{
				[,$userId] = Sharing::parseEntityValue($deletedSharing->getToEntity());
				$userIds[] = $userId;
			}
		}
		$sessionTerminationService = (new SessionTerminationServiceFactory($objectId, $userIds))->create();
		$sessionTerminationService->terminateAllSessions();
	}

	protected function processActionCopyTo($objectId, $targetObjectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		/** @var \Bitrix\Disk\Folder $targetObject */
		$targetObject = Folder::loadById((int)$targetObjectId, array('STORAGE'));
		if(!$targetObject)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $targetObject->getStorage()->getCurrentUserSecurityContext();
		if(!$targetObject->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$necessarySize = $object->getSize();
		$diskQuota = new \CDiskQuota();
		if ($necessarySize && !$diskQuota->checkDiskQuota($necessarySize))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_DISK_QUOTA'), self::ERROR_DISK_QUOTA);
			$this->sendJsonErrorResponse();
		}

		if ($object->isLink())
		{
			$newCopy = $object->getRealObject()->copyTo($targetObject, $this->getUser()->getId(), true);
			if ($newCopy)
			{
				$newCopy->rename($object->getName(), true);
			}
		}
		else
		{
			$newCopy = $object->copyTo($targetObject, $this->getUser()->getId(), true);
		}

		if(!$newCopy)
		{
			$this->errorCollection->add(
				array_filter(
					array_merge(
						[
							$object->getErrorByCode(File::ERROR_SIZE_RESTRICTION),
						],
						$object->getErrors(),
						[
							$object->getErrorByCode(File::ERROR_SIZE_RESTRICTION),
							new Error(
								Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_COPY_OBJECT'),
								self::ERROR_COULD_NOT_COPY_OBJECT
							)
						]
					)
				)
			);
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array(
			'id' => $newCopy->getId(),
			'name' => $newCopy->getName(),
			'isFolder' => $newCopy instanceof Folder,
			'destination' => [
				'id' => $targetObject->getId(),
				'name' => $targetObject->getName(),
			],
		));
	}

	private function shouldBeBlockMove(Storage $storage): bool
	{
		$proxyType = $storage->getProxyType();
		if (!($proxyType instanceof ProxyType\Common))
		{
			return false;
		}

		return !Bitrix24Manager::isFeatureEnabled('disk_common_storage');
	}

	protected function processActionMoveTo($objectId, $targetObjectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		/** @var \Bitrix\Disk\Folder $targetObject */
		$targetObject = Folder::loadById((int)$targetObjectId, array('STORAGE'));
		if(!$targetObject)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		if ($this->shouldBeBlockMove($targetObject->getStorage()))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$object->canMove($securityContext, $targetObject))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$object->moveTo($targetObject, $this->getUser()->getId(), true))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_MOVE_OBJECT'), self::ERROR_COULD_NOT_MOVE_OBJECT);
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array(
			'id' => $object->getId(),
			'name' => $object->getName(),
			'isFolder' => $object instanceof Folder,
			'destination' => [
				'id' => $targetObject->getId(),
				'name' => $targetObject->getName(),
			],
		));
	}

	protected function processActionConnectToUserStorage($objectId)
	{
		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$storage = $object->getStorage();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		if($storage->getRootObjectId() == $object->getId())
		{
			$existingSharing = Sharing::getExisting($this->getUser()->getId(), $object);
			if ($existingSharing && $existingSharing->isUnreplied())
			{
				if ($existingSharing->approve())
				{
					$sharingModel = $existingSharing;
				}
			}
			else
			{
				$sharingModel = Sharing::connectGroupToSelfUserStorage(
					$this->getUser()->getId(),
					$storage,
					$this->errorCollection
				);
			}

			if($sharingModel)
			{
				$this->sendJsonSuccessResponse(array(
					'objectName' => $object->getName(),
					'manage' => array(
						'link' => array(
							'object' => array(
								'id' => $sharingModel->getLinkObjectId(),
							)
						),
					),
					'message' => Loc::getMessage('DISK_FOLDER_LIST_MESSAGE_CONNECT_GROUP_DISK'),
				));
			}
		}
		else
		{
			$sharingModel = Sharing::connectObjectToSelfUserStorage(
				$this->getUser()->getId(),
				$object,
				$this->errorCollection
			);
		}

		if($sharingModel === null)
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'objectName' => $object->getName(),
		));
	}

	private function getObjectAndExternalLink($objectId)
	{
		/** @var File|Folder $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		$extLinks = $object->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $object->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));

		return array($object, array_pop($extLinks));
	}

	protected function processActionDisableExternalLink($objectId)
	{
		/** @var ExternalLink $extLink */
		list(, $extLink) = $this->getObjectAndExternalLink($objectId);
		if(!$extLink || $extLink->delete())
		{
			$this->sendJsonSuccessResponse();
		}
		$this->sendJsonErrorResponse();
	}

	protected function processActionGetExternalLink($objectId)
	{
		/** @var ExternalLink $extLink */
		list(, $extLink) = $this->getObjectAndExternalLink($objectId);

		if(!$extLink)
		{
			$this->sendJsonSuccessResponse(array(
				'hash' => null,
				'link' => null,
			));
		}
		$this->sendJsonSuccessResponse(array(
			'hash' => $extLink->getHash(),
			'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $extLink->getHash(),
				'action' => 'default',
			), true),
		));
	}

	protected function processActionGetDetailSettingsExternalLinkForceCreate($objectId)
	{
		$this->processActionGetDetailSettingsExternalLink($objectId, true);
	}

	protected function processActionGetDetailSettingsExternalLink($objectId, $forceCreate = false)
	{
		/** @var File|Folder $object */
		/** @var ExternalLink $extLink */
		list($object, $extLink) = $this->getObjectAndExternalLink($objectId);

		if($forceCreate && !$extLink)
		{
			$extLink = $object->addExternalLink(array(
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}

		if(!$extLink)
		{
			$this->sendJsonErrorResponse();
		}

		$size = 0;
		if ($object instanceof File)
		{
			$size = $object->getSize();
		}
		if ($object instanceof Folder)
		{
			$size = $object->countSizeOfFiles();
		}

		$this->sendJsonSuccessResponse(array(
			'object' => array(
				'name' => $object->getName(),
				'size' => \CFile::formatSize($size),
				'date' => (string)$object->getUpdateTime(),
			),
			'linkData' => array(
				'hasPassword' => $extLink->hasPassword(),
				'hasDeathTime' => $extLink->hasDeathTime(),
				'deathTime' => $extLink->hasDeathTime()? (string)$extLink->getDeathTime() : null,
				'hash' => $extLink->getHash(),
				'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
					'hash' => $extLink->getHash(),
					'action' => 'default',
				), true),
			),
		));
	}

	protected function processActionSaveSettingsExternalLink($objectId)
	{
		/** @var File|Folder $object */
		/** @var ExternalLink $extLink */
		list($object, $extLink) = $this->getObjectAndExternalLink($objectId);

		if(!$extLink)
		{
			$this->sendJsonErrorResponse();
		}

		if($this->request->getPost('deathTime'))
		{
			$extLink->changeDeathTime(DateTime::createFromTimestamp(time() + (int)$this->request->getPost('deathTime')));
		}
		if($this->request->getPost('password'))
		{
			$extLink->changePassword($this->request->getPost('password'));
		}

		$size = 0;
		if ($object instanceof File)
		{
			$size = $object->getSize();
		}
		if ($object instanceof Folder)
		{
			$size = $object->countSizeOfFiles();
		}

		$this->sendJsonSuccessResponse(array(
			'object' => array(
				'name' => $object->getName(),
				'size' => \CFile::formatSize($size),
				'date' => (string)$object->getUpdateTime(),
			),
			'linkData' => array(
				'hasPassword' => $extLink->hasPassword(),
				'hasDeathTime' => $extLink->hasDeathTime(),
				'hash' => $extLink->getHash(),
				'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
					'hash' => $extLink->getHash(),
					'action' => 'default',
				), true),
			),
		));
	}

	protected function onBeforeActionGetExternalLink()
	{
		if(!Configuration::isEnabledExternalLink())
		{
			return new EventResult(EventResult::ERROR);
		}

		return new EventResult(EventResult::SUCCESS);
	}

	protected function onBeforeActionGenerateExternalLink()
	{
		if(!Configuration::isEnabledExternalLink())
		{
			return new EventResult(EventResult::ERROR);
		}

		return new EventResult(EventResult::SUCCESS);
	}

	protected function processActionGenerateExternalLink($objectId)
	{
		/** @var File|Folder $object */
		list($object, $extLink) = $this->getObjectAndExternalLink($objectId);
		if(!$extLink)
		{
			$extLink = $object->addExternalLink(array(
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extLink)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_CREATE_FIND_EXT_LINK'), self::ERROR_COULD_NOT_CREATE_FIND_EXT_LINK);
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'hash' => $extLink->getHash(),
			'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $extLink->getHash(),
				'action' => 'default',
			), true),
		));
	}

	protected function processActionShowSubFoldersToAdd($objectId)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById((int)$objectId, array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		//we should preload specific rights by object on current level, because we are filtering by canAdd. And we can use fakeSecurityContext by $folder->getChildren
		$securityContext->preloadOperationsForChildren($folder->getRealObjectId());

		$subFolders = array();
		foreach($folder->getChildren(Driver::getInstance()->getFakeSecurityContext(), array('select' => array('*', 'HAS_SUBFOLDERS'), 'filter' => array('TYPE' => ObjectTable::TYPE_FOLDER))) as $subFolder)
		{
			/** @var Folder $subFolder */
			if($subFolder->canRead($securityContext))
			{
				$subFolders[] = array(
					'id' => $subFolder->getId(),
					'name' => $subFolder->getName(),
					'isLink' => $subFolder->isLink(),
					'canAdd' => $subFolder->canAdd($securityContext),
					'hasSubFolders' => $subFolder->hasSubFolders(),
				);
			}
		}
		\Bitrix\Main\Type\Collection::sortByColumn($subFolders, 'name');


		$this->sendJsonSuccessResponse(array(
			'items' => $subFolders,
		));
	}

	protected function processActionShowSymlinks($id, $link)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById((int)$id, array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		$parameters = array(
			'filter' => array(
				'TYPE' => FolderTable::TYPE,
				'=IS_LINK' => true,
			),
			'runtime' => array(
				new ExpressionField(
					'IS_LINK',
					'CASE WHEN %1$s <> %2$s THEN 1 ELSE 0 END',
					array('REAL_OBJECT_ID', 'ID'),
					array('data_type' => 'boolean',)
				)
			),
		);

		$subSymlinks = $folder->getDescendants(
			$securityContext,
			$parameters
		);

		$urlManager = Driver::getInstance()->getUrlManager();

		$items = array();
		foreach ($subSymlinks as $symlink)
		{
			if($symlink instanceof Folder)
			{
				$distance = \Bitrix\Disk\CrumbStorage::getInstance()->calculateDistance($folder, $symlink)?: array();
				$distance[] = $symlink->getName();

				$pathFromFolder = trim(implode('/', $distance), '/');
				$items[] = array(
					'id' => $symlink->getId(),
					'name' => $symlink->getName(),
					'link' => $link . $urlManager->encodeUrn($pathFromFolder),
					'distance' => $distance,
				);
			}
		}

		$this->sendJsonSuccessResponse(array(
			'items' => $items,
		));
	}

	protected function processActionShowShareInfoSmallView($objectId)
	{
		/** @var File|Folder $object */
		$object = \Bitrix\Disk\BaseObject::loadById((int)$objectId, array('REAL_OBJECT.STORAGE'));
		if(!$object || !$object->getRealObject() || !$object->getRealObject()->getStorage())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}
		$storage = $object->getRealObject()->getStorage();

		$proxyType = $storage->getProxyType();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		//user has only read right. And he can't see on another sharing
		if(!$object->canShare($securityContext) && !$object->canChangeRights($securityContext))
		{
			/** @var User $user */
			$user = User::getById($this->getUser()->getId());
			$entityList = array(
				array(
					'entityId' => Sharing::CODE_USER . $this->getUser()->getId(),
					'name' => $user->getFormattedName(),
					'right' => \Bitrix\Disk\RightsManager::TASK_READ,
					'avatar' => $user->getAvatarSrc(),
					'type' => 'users',
				)
			);
		}
		else
		{
			$entityList = $object->getMembersOfSharing();
		}

		$this->sendJsonSuccessResponse(array(
			'owner' => array(
				'name' => $proxyType->getEntityTitle(),
				'url' => $proxyType->getEntityUrl(),
				'avatar' => $proxyType->getEntityImageSrc(58, 58),
			),
			'members' => $entityList,
		));
	}

	protected function processActionMarkDelete($objectId)
	{
		/** @var Folder|File $objectToMarkDeleted */
		$objectToMarkDeleted = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$objectToMarkDeleted)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
			$this->sendJsonErrorResponse();
		}
		if(!$objectToMarkDeleted->canMarkDeleted($objectToMarkDeleted->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$objectToMarkDeleted->markDeleted($this->getUser()->getId()))
		{
			$this->errorCollection->add($objectToMarkDeleted->getErrors());
			$this->sendJsonErrorResponse();
		}
		$response = array();
		if($objectToMarkDeleted instanceof FolderLink)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FOLDER_DETACH');
		}
		elseif($objectToMarkDeleted instanceof FileLink)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FILE_DETACH');
		}
		elseif($objectToMarkDeleted instanceof Folder)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FOLDER_MARK_DELETED_2');
		}
		elseif($objectToMarkDeleted instanceof File)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FILE_MARK_DELETED_2');
		}

		$this->sendJsonSuccessResponse($response);
	}

	protected function processActionCalculateFileSizeAndCount($folderId)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById((int)$folderId, array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error(
				Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)
			);
			$this->sendJsonErrorResponse();
		}
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$parameters = array(
			'filter' => array(
				'PARENT_ID' => $folder->getRealObjectId(),
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
		);
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck(
			$securityContext,
			$parameters,
			array('ID', 'CREATED_BY')
		);
		$countQuery = new Query(ObjectTable::getEntity());
		$countQuery
			->addSelect(new ExpressionField('CNT', 'COUNT(1)'))
			->addSelect(new ExpressionField('FILE_SIZE', 'SUM(%s)', 'SIZE'))
			->setFilter($parameters['filter'])
		;

		foreach($parameters['runtime'] as $field)
		{
			$countQuery->registerRuntimeField('', $field);
		}

		$totalData = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		$this->sendJsonSuccessResponse(array(
			'size' => \CFile::formatSize($totalData['FILE_SIZE']),
			'count' => (int)$totalData['CNT'],
		));
	}

	protected function processActionDelete($objectId)
	{
		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$objectId, array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT);
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

	protected function processActionGetUrlToDownloadArchive($folderId, array $objectIds)
	{
		$objectIds = array_filter(array_map('intval', $objectIds));
		if(!$objectIds)
		{
			$this->errorCollection[] = new Error('Empty list of object ids');
			$this->sendJsonErrorResponse();
		}

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$folderId, array('STORAGE'));
		if(!$folder || !$folder->getStorage())
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'),
				self::ERROR_COULD_NOT_FIND_OBJECT
			);
			$this->sendJsonErrorResponse();
		}

		$fileIds = array();
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		foreach($folder->getChildren($securityContext, array('filter' => array(
			'@ID' => $objectIds,
			'TYPE' => ObjectTable::TYPE_FILE,
		))) as $file)
		{
			$fileIds[] = $file->getId();
		}

		$this->sendJsonSuccessResponse(array(
			'downloadArchiveUrl' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlDownloadController('downloadArchive', array(
				'fileId' => 0,
				'objectIds' => $fileIds,
				'signature' => \Bitrix\Disk\Security\ParameterSigner::getArchiveSignature($fileIds),
			)),
		));
	}

	protected function processActionValidateParameterAutoloadBizProc()
	{
		$validate = true;
		$search = 'bizproc';
		$data = $this->request->getPost('data');
		foreach($data['data'] as $key => $value)
		{
			$res = mb_strpos($key, $search);
			if($res === 0)
			{
				foreach($this->request->getPost('required') as $checkString)
				{
					if(is_array($value))
					{
						foreach($value as $checkType => $validateValue)
						{
							if(is_array($validateValue))
							{
								foreach($validateValue as $checkTypeArray => $validateValueArray)
								{
									if($checkTypeArray == 'TYPE' && $validateValueArray == 'html')
									{
										if(!empty($validateValue['TEXT']) && $key == $checkString)
										{
											$validate = true;
											break;
										}
										elseif($key == $checkString)
										{
											$validate = false;
										}
									}
								}
								continue;
							}
							if($checkType == 'TYPE' && $validateValue == 'html')
							{
								if(empty($value['TEXT']) && $key == $checkString)
								{
									$validate = false;
								}
							}
							elseif(empty($validateValue) && $key == $checkString)
							{
								$validate = false;
							}
							elseif(!empty($validateValue) && $key == $checkString)
							{
								$validate = true;
								break;
							}
						}
					}
					else
					{
						if(empty($value) && $key == $checkString)
						{
							$validate = false;
						}
					}
				}
			}
		}
		if(!$validate)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_VALIDATE_BIZPROC'));
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse();
	}

	protected function processActionLock($objectId)
	{
		if(!Configuration::isEnabledObjectLock())
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_LOCK_IS_DISABLED')
			);
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById($objectId, array('STORAGE'));
		if(!$file || !$file->getStorage())
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'),
				self::ERROR_COULD_NOT_FIND_OBJECT
			);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canLock($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$file->lock($this->getUser()->getId()))
		{
			$this->errorCollection->add($file->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionUnlock($objectId)
	{
		if(!Configuration::isEnabledObjectLock())
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_LOCK_IS_DISABLED')
			);
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById($objectId, array('STORAGE'));
		if(!$file || !$file->getStorage())
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'),
				self::ERROR_COULD_NOT_FIND_OBJECT
			);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canUnlock($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$file->unlock($this->getUser()->getId()))
		{
			$this->errorCollection->add($file->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionAddFolder($targetFolderId, $name)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById((int)$targetFolderId, array('STORAGE'));
		if (!$folder)
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'),
				self::ERROR_COULD_NOT_FIND_OBJECT
			);
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if (!$folder->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$subFolderModel = $folder->addSubFolder(
			array(
				'NAME' => Ui\Text::correctFolderName($name),
				'CREATED_BY' => $this->getUser()->getId()
			)
		);
		if ($subFolderModel === null)
		{
			$this->errorCollection->add($folder->getErrors());
			$this->sendJsonErrorResponse();
		}

		Application::getInstance()->addBackgroundJob(function () use ($subFolderModel) {
			DiskAnalytics::sendAddFolderEvent($subFolderModel);
		});

		$this->sendJsonSuccessResponse(
			array(
				'folder' => array(
					'id' => $subFolderModel->getId(),
				),
			)
		);
	}
}

/**
 * Class ArrayOfRights
 * @Internals
 */
final class DataSetOfRights extends \Bitrix\Disk\Type\DataSet
{
	public function filterByTaskId($taskId)
	{
		return $this->filterByField('TASK_ID', $taskId);
	}

	public function filterByAccessCode($accessCode)
	{
		return $this->filterByField('ACCESS_CODE', $accessCode);
	}

	public function filterNegative()
	{
		return $this->filterByField('NEGATIVE', true);
	}
}

$controller = new DiskFolderListAjaxController();
$controller
	->setActionName(Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;