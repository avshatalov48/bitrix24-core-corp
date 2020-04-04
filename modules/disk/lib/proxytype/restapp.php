<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Ui\Avatar;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class RestApp extends Disk
{
	/**
	 * Potential opportunity to attach object to external entity
	 * @return bool
	 */
	public function canAttachToExternalEntity()
	{
		return false;
	}

	/**
	 * Tells if objects is allowed to index by module "Search".
	 * @return bool
	 */
	public function canIndexBySearch()
	{
		return false;
	}

	/**
	 * Gets url which use for building url to listing folders, trashcan, etc.
	 * @return string
	 */
	public function getStorageBaseUrl()
	{
		return '/';
	}

	/**
	 * Get url to view entity of storage (ex. user profile, group profile, etc)
	 * By default: folder list
	 * @return string
	 */
	public function getEntityUrl()
	{
		return '/';
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
}