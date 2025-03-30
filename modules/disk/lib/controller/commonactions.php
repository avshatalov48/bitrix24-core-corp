<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Disk;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\Engine\CurrentUser;

class CommonActions extends BaseObject
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$configureActions['search'] = [
			'class' => Disk\Controller\Action\SearchAction::class,
			'+prefilters' => [
				new ActionFilter\CloseSession(),
			]
		];

		$configureActions['getArchiveLink'] = [
			'-prefilters' => [
				ActionFilter\HttpMethod::class,
			],
			'+prefilters' => [
				new ActionFilter\HttpMethod(
					[ActionFilter\HttpMethod::METHOD_POST]
				),
				new Disk\Internals\Engine\ActionFilter\HumanReadableError(),
			]
		];

		$configureActions['downloadArchive'] = [
			'-prefilters' => [
				ActionFilter\Csrf::class,
				ActionFilter\Authentication::class,
			],
			'+prefilters' => [
				new ActionFilter\Authentication(true),
				new Disk\Internals\Engine\ActionFilter\HumanReadableError(),
				new Disk\Internals\Engine\ActionFilter\CheckArchiveSignature(),
				new ActionFilter\CloseSession(),
			]
		];

		return $configureActions;
	}

	public function searchItemsAction(string $search, CurrentUser $currentUser,): array
	{
		$storageFileFinder = new Disk\Search\StorageFileFinder($currentUser->getId());
		$objects = $storageFileFinder->findModelsByText($search);

		return [
			'items' => $objects,
		];
	}

	public function resolveFolderPathAction(string $entityType, string $entityId, string $path, CurrentUser $currentUser): ?array
	{
		$storage = $this->getStorageByType($entityType, $entityId);
		if (!$storage)
		{
			$this->addError(new Error('Could not find storage.'));

			return null;
		}

		$securityContext = $storage->getSecurityContext($currentUser->getId());

		if ($path === '/')
		{
			return [
				'targetFolder' => $storage->getRootObject(),
				'breadcrumbs' => [],
			];
		}

		$resolvedData = Driver::getInstance()->getUrlManager()->resolvePathUnderRootObject($storage->getRootObject(), $path);
		if (empty($resolvedData['RELATIVE_ITEMS']))
		{
			$this->addError(new Error('Could not resolve path.'));

			return null;
		}

		[
			'RELATIVE_ITEMS' => $breadcrumbs,
			'OBJECT_ID' => $currentFolderId,
		] = $resolvedData;

		$folder = Disk\Folder::loadById($currentFolderId);
		if (!$folder)
		{
			$this->addError(new Error('Could not find folder by id.'));

			return null;
		}

		if (!$folder->canRead($securityContext))
		{
			$this->addError(new Error('Access denied.'));

			return null;
		}

		$reformattedBreadcrumbs = [];
		foreach ($breadcrumbs as $crumb)
		{
			$reformattedBreadcrumbs[] = [
				'id' => (int)$crumb['ID'],
				'name' => $crumb['NAME'],
			];
		}

		return [
			'targetFolder' => $folder,
			'breadcrumbs' => $reformattedBreadcrumbs,
		];
	}

	private function getStorageByType(string $entityType, string $entityId): ?Disk\Storage
	{
		$storage = null;
		if ($entityType === 'user')
		{
			$storage = Driver::getInstance()->getStorageByUserId((int)$entityId);
		}
		elseif ($entityType === 'group')
		{
			$storage = Driver::getInstance()->getStorageByGroupId((int)$entityId);
		}
		elseif ($entityType === 'common')
		{
			$storage = Driver::getInstance()->getStorageByCommonId($entityId);
		}

		return $storage;
	}


	public function getRightsAction(Disk\BaseObject $object, CurrentUser $currentUser): ?array
	{
		$rightsManager = Driver::getInstance()->getRightsManager();
		$storage = $object->getStorage();

		if (!$storage)
		{
			$this->addError(new Error('Could not find storage for object.'));

			return null;
		}

		$securityContext = $storage->getSecurityContext($currentUser->getId());
		$rights = $rightsManager->getAvailableActions($object, $securityContext);

		return [
			'rights' => $rights,
		];
	}

	public function getAction(Disk\BaseObject $object)
	{
		return $this->get($object);
	}

	public function getByIdsAction(
		Disk\Type\ObjectCollection $objectCollection,
	): array
	{
		$items = [];

		foreach ($objectCollection as $object)
		{
			$items[] = $this->get($object);
		}

		return [
			'items' => $items,
		];
	}

	public function renameAction(
		Disk\BaseObject $object,
		string $newName,
		bool $autoCorrect = false,
		bool $generateUniqueName = false
	)
	{
		return $this->rename($object, $newName, $autoCorrect, $generateUniqueName);
	}

	public function moveAction(Disk\BaseObject $object, Disk\Folder $toFolder)
	{
		return $this->move($object, $toFolder);
	}

	public function copyToAction(Disk\BaseObject $object, Disk\Folder $toFolder)
	{
		return $this->copyTo($object, $toFolder);
	}

	public function markDeletedAction(Disk\BaseObject $object)
	{
		$this->markDeleted($object);
	}

	public function deleteAction(Disk\BaseObject $object)
	{
		if ($object instanceof Disk\File)
		{
			$this->deleteFile($object);
		}
		else
		{
			$this->deleteFolder($object);
		}
	}

	public function restoreAction(Disk\BaseObject $object)
	{
		return $this->restore($object);
	}

	public function restoreCollectionAction(Disk\Type\ObjectCollection $objectCollection)
	{
		$restoredIds = [];
		$currentUserId = $this->getCurrentUser()->getId();
		foreach ($objectCollection as $object)
		{
			/** @var Disk\BaseObject $object */
			$securityContext = $object->getStorage()->getSecurityContext($currentUserId);
			if ($object->canRestore($securityContext))
			{
				if (!$object->restore($currentUserId))
				{
					$this->errorCollection->add($object->getErrors());
					continue;
				}

				$restoredIds[] = $object->getRealObjectId();
			}
		}

		return [
			'restoredObjectIds' => $restoredIds,
		];
	}

	public function generateExternalLinkAction(Disk\BaseObject $object)
	{
		return $this->generateExternalLink($object);
	}

	public function disableExternalLinkAction(Disk\BaseObject $object)
	{
		return $this->disableExternalLink($object);
	}

	public function getExternalLinkAction(Disk\BaseObject $object)
	{
		return $this->getExternalLink($object);
	}

	public function getAllowedOperationsRightsAction(Disk\BaseObject $object)
	{
		return $this->getAllowedOperationsRights($object);
	}

	public function getArchiveLinkAction(Disk\Type\ObjectCollection $objectCollection)
	{
		$uri = $this->getActionUri(
			'downloadArchive',
			[
				'objectCollection' => $objectCollection->getIds(),
				'signature' => Disk\Security\ParameterSigner::getArchiveSignature($objectCollection->getIds()),
			]
		);

		return [
			'downloadArchiveUri' => $uri,
		];
	}

	public function downloadArchiveAction(Disk\Type\ObjectCollection $objectCollection): ZipNginx\Archive
	{
		$archiveName = 'archive' . date('y-m-d');

		return ZipNginx\Archive::createByObjects($archiveName, $objectCollection, $this->getCurrentUser()?->getId());
	}

	public function listRecentlyUsedAction()
	{
		$recentlyUsedManager = Driver::getInstance()->getRecentlyUsedManager();

		return [
			'files' => $recentlyUsedManager->getFileModelListByUser($this->getCurrentUser()),
		];
	}
}