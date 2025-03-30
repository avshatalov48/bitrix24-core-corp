<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\User;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\RestException;
use Bitrix\Disk\Rest\Entity;
use Bitrix\Disk;

final class Storage extends Base
{
	/**
	 * Returns field descriptions (type, possibility to usage in filter, in render).
	 * @return array
	 */
	protected function getFields()
	{
		$storage = new Entity\Storage;
		return $storage->getFields();
	}

	/**
	 * Returns Storage by id.
	 * @param int $id Id of storage.
	 * @return Disk\Storage
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function get($id)
	{
		if (!is_numeric($id))
		{
			return null;
		}

		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canRead($securityContext))
		{
			throw new AccessException;
		}

		return $storage;
	}

	/**
	 * Returns storage for current application.
	 * @return Disk\Storage|null
	 * @throws RestException
	 */
	protected function getForApp()
	{
		if(!$this->restServer->getAppId())
		{
			throw new AccessException("Application context required");
		}

		$driver  = Disk\Driver::getInstance();
		$appData = AppTable::getList(array('filter' => array('=CLIENT_ID' => $this->restServer->getAppId())))->fetch();
		if(!$appData || empty($appData['ID']) || empty($appData['CODE']))
		{
			throw new RestException('Could not find application by app_id.', RestException::ERROR_NOT_FOUND);
		}

		$storage = $driver->getStorageByRestApp($appData['ID']);
		if(!$storage)
		{
			$storage = $driver->addRestAppStorage(array(
				'ENTITY_ID' => $appData['ID'],
				'NAME' => $appData['CODE'],
			));
			if(!$storage)
			{
				$this->errorCollection->add($driver->getErrors());
				return null;
			}
		}

		return $storage;
	}

	/**
	 * Returns list of storages.
	 * @param array $filter Filter.
	 * @param array $order  Order.
	 * @return Disk\Storage[]|null
	 */
	protected function getList(array $filter = array(), array $order = array())
	{
		$securityContext = $this->getSecurityContextByUser($this->userId);

		$internalizer = new Disk\Rest\Internalizer(new Entity\Storage, $this);
		$navData = Disk\Rest\RestManager::getNavData($this->start);
		$parameters = array_merge(
			array(
				'with' => array('ROOT_OBJECT'),
				'filter' => array_merge(array(
					'=ROOT_OBJECT.PARENT_ID' => null,
					'=MODULE_ID' => Disk\Driver::INTERNAL_MODULE_ID,
					'=RIGHTS_CHECK' => true,
				), $internalizer->cleanFilter($filter)),
				'runtime' => array(
					new ExpressionField('RIGHTS_CHECK', 'CASE WHEN ' . $securityContext->getSqlExpressionForList('%1$s', '%2$s') . ' THEN 1 ELSE 0 END', array(
							'ROOT_OBJECT.ID',
							'ROOT_OBJECT.CREATED_BY'
						), array('data_type' => 'boolean',))
				),
				'order' => $order,
				'count_total' => true,
			),
			$navData
		);

		$parameters = Disk\Driver::getInstance()
			->getRightsManager()
			->addRightsCheck(
				$securityContext,
				$parameters,
				array( 'ROOT_OBJECT.ID', 'ROOT_OBJECT.CREATED_BY')
			)
		;

		$storages = array();
		$storageRows = Disk\Storage::getList($parameters);
		foreach($storageRows as $storageRow)
		{
			$storage = Disk\Storage::buildFromArray($storageRow);

			if(
				!$storage->getProxyType() instanceof Disk\ProxyType\Common &&
				!$storage->getProxyType() instanceof Disk\ProxyType\Group &&
				!$storage->getProxyType() instanceof Disk\ProxyType\User
			)
			{
				continue;
			}

			$storages[] = $storage;
		}
		unset($storage);

		return Disk\Rest\RestManager::setNavData(
			$storages,
			array(
				"count" => $storageRows->getCount(),
				"offset" => $navData['offset'],
			)
		);
	}

	private function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new Disk\Security\DiskSecurityContext($user);
		if(Loader::includeModule('socialnetwork'))
		{

			if(\CSocnetUser::isCurrentUserModuleAdmin())
			{
				$diskSecurityContext = new Disk\Security\FakeSecurityContext($user);
			}
		}
		if(User::isCurrentUserAdmin())
		{
			$diskSecurityContext = new Disk\Security\FakeSecurityContext($user);
		}
		return $diskSecurityContext;
	}

	/**
	 * Gets type of storages.
	 * @return array
	 */
	protected function getTypes()
	{
		return array('user', 'common', 'group');
	}

	/**
	 * Renames storage.
	 * @param int    $id      Id of storage.
	 * @param string $newName New name for storage.
	 * @return Disk\Storage|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function rename($id, $newName)
	{
		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canRename($securityContext))
		{
			throw new AccessException;
		}
		if(!$storage->getProxyType() instanceof Disk\ProxyType\RestApp)
		{
			throw new RestException('Access denied (invalid type of storage)');
		}
		if(!$storage->rename($newName))
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $storage;
	}

	private function validateRights(array $rights): Result
	{
		$result = new Result();
		if (empty($rights))
		{
			return $result;
		}

		foreach ($rights as $right)
		{
			if (!\is_array($right))
			{
				$result->addError(new Error('Invalid format: Right should be array'));

				return $result;
			}

			if (!isset($right['ACCESS_CODE'], $right['TASK_ID']))
			{
				$result->addError(new Error('Invalid format: Right should contain ACCESS_CODE and TASK_ID'));

				return $result;
			}
		}

		return $result;
	}

	/**
	 * Creates folder in root of storage.
	 * @param      int $id     Id of storage.
	 * @param array    $data   Data for creating new folder.
	 * @param array    $rights Specific rights on folder. If empty, then use parents rights.
	 * @return Disk\Folder|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function addFolder($id, array $data, array $rights = array())
	{
		if (!is_numeric($id))
		{
			return null;
		}

		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canAdd($securityContext))
		{
			throw new AccessException;
		}
		if ($rights && !$storage->getRootObject()->canChangeRights($securityContext))
		{
			throw new AccessException;
		}

		$validationRights = $this->validateRights($rights);
		if (!$validationRights->isSuccess())
		{
			throw new RestException('Invalid rights format');
		}

		$folder = $storage->addFolder(array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights);
		if(!$folder)
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $folder;
	}

	/**
	 * Returns direct children of storage.
	 * @param    int $id     Id of storage.
	 * @param array  $filter Filter.
	 * @param array  $order  Order.
	 * @return Disk\BaseObject[]
	 * @throws RestException
	 */
	protected function getChildren($id, array $filter = array(), array $order = array())
	{
		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();

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
		$childrenRows = Disk\Internals\FolderTable::getChildren($storage->getRootObjectId(), $parameters);
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
	 * Creates new file in root of storage.
	 * @param       int    $id          Id of storage.
	 * @param string|array $fileContent File content. General format in REST.
	 * @param array        $data        Data for new file.
	 * @param array        $rights      Specific rights on file. If empty, then use parents rights.
	 * @return Disk\File|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function uploadFile($id, $fileContent, array $data, array $rights = array(), $generateUniqueName = false)
	{
		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$storage = $this->getStorageById((int)$id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canAdd($securityContext))
		{
			throw new AccessException;
		}
		if ($rights && !$storage->getRootObject()->canChangeRights($securityContext))
		{
			throw new AccessException;
		}

		$validationRights = $this->validateRights($rights);
		if (!$validationRights->isSuccess())
		{
			throw new RestException('Invalid rights format');
		}

		$fileData = \CRestUtil::saveFile($fileContent);
		if(!$fileData)
		{
			throw new RestException('Could not save file.');
		}
		$file = $storage->uploadFile($fileData, array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights, $generateUniqueName);
		if(!$file)
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $file;
	}

	/**
	 * @param $id
	 * @return Disk\Storage
	 * @throws RestException
	 */
	private function getStorageById(int $id)
	{
		$storage = Disk\Storage::getById($id);
		if(!$storage)
		{
			throw new RestException("Could not find entity with id '{$id}'.", RestException::ERROR_NOT_FOUND);
		}

		return $storage;
	}
}