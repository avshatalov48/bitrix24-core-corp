<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\Internals;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

abstract class BaseObject extends Internals\Engine\Controller
{
	protected function get(Disk\BaseObject $object)
	{
		return [
			'object' => $object->jsonSerialize(),
		];
	}

	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		$defaultPreFilters = parent::getDefaultPreFilters();
		$defaultPreFilters[] = new Internals\Engine\ActionFilter\CheckReadPermission();

		return $defaultPreFilters;
	}

	protected function getDefaultPostFilters()
	{
		$defaultPostFilters = parent::getDefaultPostFilters();
		$defaultPostFilters[] = new Internals\Engine\ActionFilter\HumanReadableError();

		return $defaultPostFilters;
	}

	protected function rename(Disk\BaseObject $object, $newName, $autoCorrect = false)
	{
		$securityContext = $object->getStorage()->getSecurityContext($this->getCurrentUser()->getId());
		if (!$object->canRename($securityContext))
		{
			$this->addError(new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED')));

			return;
		}

		if ($autoCorrect)
		{
			$newName = Disk\Ui\Text::correctFilename($newName);
		}

		if (!$object->rename($newName))
		{
			$this->addErrors($object->getErrors());

			return;
		}

		return $this->get($object);
	}

	protected function deleteFile(Disk\File $file)
	{
		$securityContext = $file->getStorage()->getSecurityContext($this->getCurrentUser()->getId());
		if (!$file->canDelete($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$file->delete($this->getCurrentUser()->getId()))
		{
			$this->errorCollection->add($file->getErrors());
		}
	}

	protected function deleteFolder(Disk\Folder $folder)
	{
		$securityContext = $folder->getStorage()->getSecurityContext($this->getCurrentUser()->getId());
		if (!$folder->canDelete($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$folder->deleteTree($this->getCurrentUser()->getId()))
		{
			$this->errorCollection->add($folder->getErrors());
		}
	}

	protected function markDeleted(Disk\BaseObject $object)
	{
		$securityContext = $object->getStorage()->getSecurityContext($this->getCurrentUser()->getId());
		if (!$object->canMarkDeleted($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$object->markDeleted($this->getCurrentUser()->getId()))
		{
			$this->errorCollection->add($object->getErrors());
		}
	}

	protected function restore(Disk\BaseObject $object)
	{
		$securityContext = $object->getStorage()->getSecurityContext($this->getCurrentUser()->getId());
		if (!$object->canRestore($securityContext))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		if (!$object->restore($this->getCurrentUser()->getId()))
		{
			$this->errorCollection->add($object->getErrors());

			return;
		}

		//@see \Bitrix\Disk\FileLink::restore and \Bitrix\Disk\BaseObject::restoreByLinkObject
		//in this case the object which we want to restore is original. We can't get symlink now.
		return $this->get(
			$object->getRealObject()
		);
	}

	protected function move(Disk\BaseObject $object, Disk\Folder $toFolder)
	{
		$securityContext = $object->getStorage()->getSecurityContext($this->getCurrentUser()->getId());
		if (!$object->canMove($securityContext, $toFolder))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_ERROR_MESSAGE_DENIED'));

			return;
		}

		$movedObject = $object->moveTo($toFolder, $this->getCurrentUser()->getId(), true);
		if (!$movedObject)
		{
			$this->errorCollection->add($object->getErrors());

			return;
		}

		return $this->get($movedObject);
	}

	protected function generateExternalLink(Disk\BaseObject $object)
	{
		$extLink = $this->getExternalLink($object);
		if (!$extLink)
		{
			$extLink = $object->addExternalLink(array(
				'CREATED_BY' => $this->getCurrentUser()->getId(),
				'TYPE' => ExternalLink::TYPE_MANUAL,
			));
		}

		if (!$extLink)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("DISK_FILE_C_ERROR_COULD_NOT_CREATE_FIND_EXT_LINK"));

			return null;
		}

		$link = new Uri(Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
			'hash' => $extLink->getHash(),
			'action' => 'default',
		), true));

		return [
			'externalLink' => [
				'id' => $extLink->getId(),
				'objectId' => $extLink->getObjectId(),
				'hash' => $extLink->getHash(),
				'link' => $link,
				'hasPassword' => $extLink->hasPassword(),
				'hasDeathTime' => $extLink->hasDeathTime(),
				'deathTime' => $extLink->getDeathTime(),
				'deathTimeTimestamp' => $extLink->hasDeathTime()? $extLink->getDeathTime()->getTimestamp() : null,
			],
		];
	}

	protected function disableExternalLink(Disk\BaseObject $object)
	{
		$extLink = $this->getExternalLink($object);
		if (!$extLink || $extLink->delete())
		{
			return true;
		}

		return false;
	}

	/**
	 * @param Disk\BaseObject $object
	 *
	 * @return Disk\ExternalLink|null
	 */
	private function getExternalLink(Disk\BaseObject $object)
	{
		$extLinks = $object->getExternalLinks([
			'filter' => [
				'OBJECT_ID' => $object->getId(),
				'CREATED_BY' => $this->getCurrentUser()->getId(),
				'TYPE' => ExternalLink::TYPE_MANUAL,
				'=IS_EXPIRED' => false,
			],
			'limit' => 1,
		]);

		return array_pop($extLinks);
	}

	protected function getAllowedOperationsRights(Disk\BaseObject $object)
	{
		$userId = $this->getCurrentUser()->getId();

		$rightsManager = Disk\Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getSecurityContext($userId);
		if ($securityContext instanceof Disk\Security\FakeSecurityContext)
		{
			$operations = $rightsManager->listOperations();
		}
		else
		{
			$operations = $rightsManager->getUserOperationsByObject(
				$object->getRealObjectId(),
				$userId
			);
		}

		return [
			'operations' => $operations,
		];
	}
}