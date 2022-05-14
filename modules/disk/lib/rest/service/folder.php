<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Disk\Rest\Entity;
use Bitrix\Disk;

final class Folder extends BaseObject
{
	const FILE_FORM_FIELD = 'file';

	/**
	 * Returns field descriptions (type, possibility to usage in filter, in render).
	 * @return array
	 */
	protected function getFields()
	{
		$storage = new Entity\Folder;
		return $storage->getFields();
	}

	/**
	 * Returns folder by id.
	 * @param int $id Id of folder.
	 * @return Disk\Folder
	 */
	protected function getWorkObjectById($id)
	{
		return $this->getFolderById($id);
	}

	/**
	 * Returns direct children of storage.
	 * @param      int $id     Id of folder.
	 * @param array    $filter Filter.
	 * @param array    $order  Order.
	 * @return Disk\BaseObject[]
	 * @throws RestException
	 */
	protected function getChildren($id, array $filter = array(), array $order = array())
	{
		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();

		$internalizer = new Disk\Rest\Internalizer(new Entity\BaseObject, $this);
		$navData = Disk\Rest\RestManager::getNavData($this->start);
		$parameters = array_merge(array(
			'filter' => $internalizer->cleanFilter($filter),
			'order' => $order,
			'count_total' => true,
		), $navData);

		$parameters['filter']['DELETED_TYPE'] = Disk\Internals\ObjectTable::DELETED_TYPE_NONE;
		$parameters = Disk\Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$children = array();
		$childrenRows = Disk\Internals\FolderTable::getChildren($folder->getRealObjectId(), $parameters);
		foreach ($childrenRows as $childrenRow)
		{
			$children[] = Disk\BaseObject::buildFromArray($childrenRow);
		}

		return Disk\Rest\RestManager::setNavData(
			$children,
			array(
				"count" => $childrenRows->getCount(),
				"offset" => $navData['offset'],
			)
		);
	}

	/**
	 * Creates sub-folder in folder.
	 * @param      int $id     Id of storage.
	 * @param array    $data   Data for creating new folder.
	 * @param array    $rights Specific rights on folder. If empty, then use parents rights.
	 * @return Disk\Folder|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function addSubFolder($id, array $data, array $rights = array())
	{
		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			throw new AccessException;
		}
		if ($rights && !$folder->canChangeRights($securityContext))
		{
			throw new AccessException;
		}

		$subFolder = $folder->addSubFolder(array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights);
		if(!$subFolder)
		{
			$this->errorCollection->add($folder->getErrors());
			return null;
		}

		return $subFolder;
	}

	/**
	 * Deletes folder and sub-items.
	 * @param int $id Id of folder.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function deleteTree($id)
	{
		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canDelete($securityContext))
		{
			throw new AccessException;
		}
		if(!$folder->getParentId())
		{
			throw new RestException('Could not delete root folder.');
		}
		if(!$folder->deleteTree($this->userId))
		{
			$this->errorCollection->add($folder->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * Creates new file in folder.
	 * @param       int    $id          Id of folder.
	 * @param string|array $fileContent File content. General format in REST.
	 * @param array        $data        Data for new file.
	 * @param array        $rights      Specific rights on file. If empty, then use parents rights.
	 * @return Disk\File|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function uploadFile($id, array $data = array(), $fileContent = null, array $rights = array(), $generateUniqueName = false)
	{
		if($fileContent === null)
		{
			return array(
				'field' => self::FILE_FORM_FIELD,
				'uploadUrl' => \CRestUtil::getUploadUrl(
					array('id' => $id, 'data' => $data, 'rights' => $rights, 'generateUniqueName' => $generateUniqueName),
					$this->restServer
				),
			);
		}

		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			throw new AccessException;
		}
		if ($rights && !$folder->canChangeRights($securityContext))
		{
			throw new AccessException;
		}

		$fileData = \CRestUtil::saveFile($fileContent);
		if(!$fileData)
		{
			throw new RestException('Could not save file.');
		}
		$file = $folder->uploadFile($fileData, array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights, $generateUniqueName);
		if(!$file)
		{
			$this->errorCollection->add($folder->getErrors());
			return null;
		}

		return $file;
	}

	protected function upload($id, array $data = array(), array $rights = array(), $generateUniqueName = false)
	{
		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			throw new AccessException;
		}
		if ($rights && !$folder->canChangeRights($securityContext))
		{
			throw new AccessException;
		}

		if(empty($_FILES[self::FILE_FORM_FIELD]))
		{
			$this->errorCollection[] = new Error("Error: required parameter " . self::FILE_FORM_FIELD, self::ERROR_REQUIRED_PARAMETER);

			return null;
		}

		$file = $folder->uploadFile($_FILES[self::FILE_FORM_FIELD], array(
			'NAME' => $_FILES[self::FILE_FORM_FIELD]['name'],
			'CREATED_BY' => $this->userId
		), $rights, $generateUniqueName);

		if(!$file)
		{
			$this->errorCollection->add($folder->getErrors());
			return null;
		}

		return $file;
	}

	protected function shareToUser($id, $userId, $taskName)
	{
		$userId = (int)$userId;
		$folder = $this->getFolderById($id);
		if (!$this->isDiskStorage($folder))
		{
			throw new AccessException;
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		$rightsManager = Disk\Driver::getInstance()->getRightsManager();
		if (!$folder->canShare($securityContext) || !$rightsManager->isValidTaskName($taskName))
		{
			throw new AccessException;
		}

		$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($folder, $this->userId);
		if ($rightsManager->pseudoCompareTaskName($taskName, $maxTaskName) > 0)
		{
			throw new AccessException;
		}

		$sharing = Disk\Sharing::add(
			[
				'FROM_ENTITY' => Disk\Sharing::CODE_USER . $this->userId,
				'REAL_OBJECT' => $folder,
				'CREATED_BY' => $this->userId,
				'CAN_FORWARD' => false,
				'TO_ENTITY' => Disk\Sharing::CODE_USER . $userId,
				'TASK_NAME' => $taskName,
			],
			$this->errorCollection
		);

		if(!$sharing)
		{
			return null;
		}

		return true;
	}

	private function isDiskStorage(Disk\Folder $folder)
	{
		$proxyType = $folder->getStorage()->getProxyType();

		return
			$proxyType instanceof Disk\ProxyType\User ||
			$proxyType instanceof Disk\ProxyType\Group ||
			$proxyType instanceof Disk\ProxyType\Common
		;
	}
}