<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\SystemUser;
use Bitrix\Disk\User as UserModel;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class User extends Disk
{
	public const SUFFIX_DOCUMENTS_LIST = 'documents/';
	public const SUFFIX_BOARDS_LIST = 'boards/';

	/** @var \Bitrix\Disk\User */
	private $user;

	private string $entityUrl = '';

	/**
	 * Potential opportunity to attach object to external entity
	 * @return bool
	 */
	public function canAttachToExternalEntity()
	{
		return true;
	}

	/**
	 * Sets the entity url for building url to listing folders, trashcan, etc.
	 *
	 * @param string $sefUrl Root url.
	 * @return void
	 */
	public function setEntityUrl(string $entityUrl): void
	{
		$this->entityUrl = $entityUrl;
	}

	/**
	 * Get url to view entity of storage (ex. user profile, group profile, etc)
	 * By default: folder list
	 * @return string
	 */
	public function getEntityUrl()
	{
		if ($this->entityUrl)
		{
			return $this->entityUrl;
		}

		return $this->getUser()->getDetailUrl();
	}

	/**
	 * Get name of entity (ex. user last name + first name, group name, etc)
	 * By default: get title
	 * @return string
	 */
	public function getEntityTitle()
	{
		$user = $this->getUser();

		return isset($user)? $user->getFormattedName() : parent::getEntityTitle();
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
		return $this->getUser()->getAvatarSrc($width, $height);
	}

	/**
	 * Return name of storage.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('DISK_PROXY_TYPE_USER_TITLE');
	}

	/**
	 * Return name of storage.
	 * May be concrete by current user context.
	 * Should not use in notification, email to another person.
	 * @return string
	 */
	public function getTitleForCurrentUser()
	{
		global $USER;
		if($USER instanceof \CUser && $USER->getId() == $this->entityId)
		{
			return Loc::getMessage('DISK_PROXY_TYPE_USER_TITLE_CURRENT_USER');
		}

		return parent::getTitle();
	}

	/**
	 * Returns user model for the proxy type.
	 * @return \Bitrix\Disk\User|null
	 */
	public function getUser()
	{
		if($this->user !== null)
		{
			return $this->user;
		}

		$this->user = UserModel::loadById($this->entityId);
		if(!$this->user)
		{
			$this->user = SystemUser::create();
		}

		return $this->user;
	}

	/**
	 * Gets url on documents.
	 * @return string
	 */
	public function getBaseUrlDocumentList(): string
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_DOCUMENTS_LIST;
	}

	/**
	 * Gets url for boards list.
	 * @return string
	 */
	public function getBaseUrlBoardsList(): string
	{
		return '/' . trim($this->getStorageBaseUrl(), '/') . '/' . static::SUFFIX_BOARDS_LIST;
	}
}