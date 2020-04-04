<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Storage;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class Base
{
	const SUFFIX_FOLDER_LIST          = 'path/';
	const SUFFIX_FILE_DETAIL          = 'file/';
	const SUFFIX_FILE_HISTORY         = 'file-history/';
	const SUFFIX_TRASHCAN_LIST        = 'trashcan/';
	const SUFFIX_TRASHCAN_DETAIL_FILE = 'trash/file/';
	const SUFFIX_DISK                 = 'disk/';

	protected $entityId;
	protected $entityMiscData;
	/** @var Storage */
	protected $storage;

	/**
	 * Constructor
	 * @param string  $entityId Id of entity.
	 * @param Storage $storage Storage which belongs to entity.
	 * @param null    $entityMiscData Container with different data.
	 */
	public function __construct($entityId, Storage $storage, $entityMiscData = null)
	{
		$this->entityId = $entityId;
		$this->entityMiscData = $entityMiscData;
		$this->storage = $storage;
	}

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

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
	 * Gets security context for current user.
	 * @return SecurityContext
	 */
	public function getSecurityContextByCurrentUser()
	{
		global $USER;

		return $this->getSecurityContextByUser($USER);
	}

	/**
	 * Gets security context (access provider) for user.
	 * Attention! File/Folder can use anywhere and SecurityContext have to check rights anywhere (any module).
	 * @param mixed $user User which use for check rights.
	 * @return SecurityContext
	 */
	abstract public function getSecurityContextByUser($user);

	/**
	 * Gets url which use for building url to listing folders, trashcan, etc.
	 * @return string
	 */
	abstract public function getStorageBaseUrl();

	/**
	 * Gets url on listing root folder in storage.
	 * @return string
	 */
	public function getBaseUrlFolderList()
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_FOLDER_LIST;
	}

	/**
	 * Gets url on listing root of trash can in storage.
	 * @return string
	 */
	public function getBaseUrlTashcanList()
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_TRASHCAN_LIST;
	}

	/**
	 * Gets url on detail of file.
	 * @return string
	 */
	public function getBaseUrlFileDetail()
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_FILE_DETAIL;
	}

	/**
	 * Gets url on history of file.
	 * @return string
	 */
	public function getBaseUrlFileHistory()
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_FILE_HISTORY;
	}

	/**
	 * Gets url on detail of file in trash can.
	 * @return string
	 */
	public function getBaseUrlTrashcanFileDetail()
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_TRASHCAN_DETAIL_FILE;
	}

	/**
	 * Get url to view entity of storage (ex. user profile, group profile, etc)
	 * By default: folder list
	 * @return string
	 */
	public function getEntityUrl()
	{
		return $this->getBaseUrlFolderList();
	}

	/**
	 * Get name of entity (ex. user last name + first name, group name, etc)
	 * By default: get title
	 * @return string
	 */
	public function getEntityTitle()
	{
		return $this->getTitle();
	}

	/**
	 * Get image (avatar) of entity.
	 * Can be shown with entityTitle in different lists.
	 * @param int $width Image width.
	 * @param int $height Image height.
	 * @return string
	 */
	abstract public function getEntityImageSrc($width, $height);

	/**
	 * Return name of storage.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('DISK_PROXY_TYPE_BASE_TITLE', array(
			'#NAME#' => $this->storage->getName(),
		));
	}

	/**
	 * Return name of storage.
	 * May be concrete by current user context.
	 * Should not use in notification, email to another person.
	 * @return string
	 */
	public function getTitleForCurrentUser()
	{
		return $this->getTitle();
	}
}