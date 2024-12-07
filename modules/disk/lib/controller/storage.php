<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
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
	 * @return Disk\Storage[]
	 */
	public function getAction(Disk\Storage $storage): array
	{
		return [
			'storage' => $storage,
		];
	}

	/**
	 * Returns personal storage of current user.
	 * @return array
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getPersonalStorageAction(): array
	{
		$userId = $this->getCurrentUser()?->getId();
		if (!$userId)
		{
			$this->addError(new Error('Could not find current user.'));

			return [];
		}

		$storage = Disk\Driver::getInstance()->getStorageByUserId($userId);
		if (!$storage)
		{
			$this->addError(new Error('Could not find personal storage.'));

			return [];
		}

		return $this->getAction($storage);
	}

	/**
	 * Returns common storage of current site.
	 * Use id "shared_files_{siteId}" for determine common storage.
	 * @return array
	 */
	public function getCommonStorageAction(): array
	{
		$siteId = Application::getInstance()->getContext()->getSite();
		$commonStorageId = 'shared_files_' . $siteId;
		$storage = Disk\Driver::getInstance()->getStorageByCommonId($commonStorageId);

		if (!$storage)
		{
			$this->addError(new Error("Could not find common storage. Site: {$siteId}"));

			return [];
		}

		return $this->getAction($storage);
	}

	/**
	 * Returns storage by social group.
	 * @param int $groupId Social group id.
	 * @return array
	 */
	public function getBySocialGroupAction(int $groupId): array
	{
		$storage = Disk\Driver::getInstance()->getStorageByGroupId($groupId);
		if (!$storage)
		{
			$this->addError(new Error('Could not find storage by social group.'));

			return [];
		}

		$securityContext = $storage->getSecurityContext($this->getCurrentUser()?->getId());
		if (!$storage->canRead($securityContext))
		{
			$this->addError(new Error('Could not find storage by social group.'));

			return [];
		}

		return $this->getAction($storage);
	}
}