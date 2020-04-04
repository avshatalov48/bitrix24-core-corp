<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Storage;
use Bitrix\Disk\Ui\Avatar;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Common extends Disk
{
	protected $unserializedMiscData;

	public function __construct($entityId, Storage $storage, $entityMiscData = null)
	{
		parent::__construct($entityId, $storage, $entityMiscData);

		if(!empty($this->entityMiscData) && is_string($this->entityMiscData))
		{
			$this->unserializedMiscData = unserialize($this->entityMiscData);
		}
	}

	/**
	 * Potential opportunity to attach object to external entity
	 * @return bool
	 */
	public function canAttachToExternalEntity()
	{
		return true;
	}

	/**
	 * Gets url which use for building url to listing folders, trashcan, etc.
	 * @return string
	 */
	public function getStorageBaseUrl()
	{
		if(!empty($this->unserializedMiscData['BASE_URL']))
		{
			return '/' . ltrim(\CComponentEngine::makePathFromTemplate($this->unserializedMiscData['BASE_URL']), '/');
		}

		return '/common/' . $this->entityId . '/files/';
	}

	/**
	 * Get image (avatar) of entity.
	 * Can be shown with entityTitle in different lists.
	 * @param int $width Image width.
	 * @param int $height Image height.
	 * @return string
	 */
	public function getEntityImageSrc($width, $height)
	{
		return Avatar::getDefaultGroup();
	}

	/**
	 * Return name of storage.
	 * @return string
	 */
	public function getTitle()
	{
		$entityId = $this->storage->getEntityId();
		if($entityId == 'shared_files_s1' || $entityId == 'shared_files' || $entityId == 'shared')
		{
			return Loc::getMessage('DISK_PROXY_TYPE_COMMON_TITLE_S1');
		}

		return parent::getTitle();
	}
}