<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Version;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Disk;
use Bitrix\Disk\UrlManager;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
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
			],
		];

		$configureActions['getArchiveLink'] = [
			'-prefilters' => [
				ActionFilter\HttpMethod::class,
			],
			'+prefilters' => [
				new ActionFilter\HttpMethod(
					[ActionFilter\HttpMethod::METHOD_POST],
				),
				new Disk\Internals\Engine\ActionFilter\HumanReadableError(),
			],
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
			],
		];

		return $configureActions;
	}

	public function searchItemsAction(string $search, CurrentUser $currentUser): array
	{
		$storageFileFinder = new Disk\Search\StorageFileFinder($currentUser->getId());
		$objects = $storageFileFinder->findModelsByText($search);

		return [
			'items' => $objects,
		];
	}

	public function resolveFolderPathAction(
		string $entityType,
		string $entityId,
		string $path,
		CurrentUser $currentUser,
	): ?array
	{
		$result = (new UrlManager())->resolveFolderPath($entityType, $entityId, $path, $currentUser);
		if (!$result->isSuccess())
		{
			$this->addError($result->getError());

			return null;
		}

		return $result->getData();
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
		bool $generateUniqueName = false,
	) {
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
			],
		);

		return [
			'downloadArchiveUri' => $uri,
		];
	}

	public function checkFileLimitAction(Disk\Type\ObjectCollection $objectCollection)
	{
		$isFileLimitExceeded = false;

		$userId = $this->getCurrentUser()?->getId();
		if (ZipNginx\Archive::isFileLimitExceededByObjects($objectCollection, $userId))
		{
			$isFileLimitExceeded = true;
		}

		return [
			'isFileLimitExceeded' => $isFileLimitExceeded,
		];
	}

	public function downloadArchiveAction(Disk\Type\ObjectCollection $objectCollection): ZipNginx\Archive
	{
		$archiveName = 'archive' . date('y-m-d');
		$userId = $this->getCurrentUser()?->getId();

		return ZipNginx\Archive::createByObjects($archiveName, $objectCollection, $userId);
	}

	public function listRecentlyUsedAction()
	{
		$recentlyUsedManager = Driver::getInstance()->getRecentlyUsedManager();

		return [
			'files' => $recentlyUsedManager->getFileModelListByUser($this->getCurrentUser()),
		];
	}
}
