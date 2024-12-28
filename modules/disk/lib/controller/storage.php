<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\SystemException;

final class Storage extends Engine\Controller
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\Storage::class, 'storage', function($className, $id){
			return Disk\Storage::loadById($id);
		});
	}

	public function isEnabledSizeLimitRestrictionAction(Disk\Storage $storage)
	{
		if ($storage->isEnabledSizeLimitRestriction())
		{
			return [
				'isEnabledSizeLimitRestriction' => $storage->isEnabledSizeLimitRestriction(),
				'sizeLimitRestriction' => $storage->getSizeLimit(),
			] ;
		}

		return [
			'isEnabledSizeLimitRestriction' => false,
		];
	}

	/**
	 * Returns basic storage info.
	 * @param Disk\Storage $storage Storage, loaded by primary auto wired parameter.
	 * @param CurrentUser $currentUser Current user injected by param autowiring.
	 * @return Disk\Storage[]|null
	 */
	public function getAction(Disk\Storage $storage, CurrentUser $currentUser): ?array
	{
		$securityContext = $storage->getSecurityContext($currentUser->getId());
		if (!$storage->canRead($securityContext))
		{
			$this->addError(new Error('No permission to read storage.'));

			return null;
		}

		return [
			'storage' => $storage,
		];
	}

	/**
	 * Returns personal storage of current user.
	 * @param CurrentUser $currentUser Current user injected by param autowiring.
	 * @return array|null
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getPersonalStorageAction(CurrentUser $currentUser): ?array
	{
		$userId = $currentUser->getId();
		if (!$userId)
		{
			$this->addError(new Error('Could not find current user.'));

			return null;
		}

		$storage = Disk\Driver::getInstance()->getStorageByUserId($userId);
		if (!$storage)
		{
			$this->addError(new Error('Could not find personal storage.'));

			return null;
		}

		return $this->getAction($storage, $currentUser);
	}

	/**
	 * Returns common storage of current site.
	 * @param CurrentUser $currentUser Current user injected by param autowiring.
	 * Use id "shared_files_{siteId}" for determine common storage.
	 * @return array|null
	 */
	public function getCommonStorageAction(CurrentUser $currentUser): ?array
	{
		$siteId = Application::getInstance()->getContext()->getSite();
		$commonStorageId = 'shared_files_' . $siteId;
		$storage = Disk\Driver::getInstance()->getStorageByCommonId($commonStorageId);

		if (!$storage)
		{
			$this->addError(new Error("Could not find common storage. Site: {$siteId}"));

			return null;
		}

		return $this->getAction($storage, $currentUser);
	}

	/**
	 * Returns storage by social group.
	 * @param int $groupId Social group id.
	 * @param CurrentUser $currentUser Current user injected by param autowiring.
	 * @return array|null
	 */
	public function getBySocialGroupAction(int $groupId, CurrentUser $currentUser): ?array
	{
		$storage = Disk\Driver::getInstance()->getStorageByGroupId($groupId);
		if (!$storage)
		{
			$this->addError(new Error('Could not find storage by social group.'));

			return null;
		}

		$securityContext = $storage->getSecurityContext($currentUser->getId());
		if (!$storage->canRead($securityContext))
		{
			$this->addError(new Error('Could not find storage by social group.'));

			return null;
		}

		return $this->getAction($storage, $currentUser);
	}
}