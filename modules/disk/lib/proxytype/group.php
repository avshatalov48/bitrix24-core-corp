<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Storage;
use Bitrix\Disk\Ui\Avatar;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Group extends Disk
{
	private $group;
	private $photoCache = array();

	/**
	 * Potential opportunity to attach object to external entity
	 * @return bool
	 */
	public function canAttachToExternalEntity()
	{
		return true;
	}

	/**
	 * Get url to view entity of storage (ex. user profile, group profile, etc)
	 * By default: folder list
	 * @return string
	 */
	public function getEntityUrl()
	{
		$groupPage = \COption::getOptionString("socialnetwork", "workgroups_page", false, SITE_ID);
		if(!$groupPage)
		{
			$groupPage = SITE_DIR . 'workgroups/';
		}

		return $groupPage . 'group/' .  $this->entityId . '/';
	}

	/**
	 * Get name of entity (ex. user last name + first name, group name, etc)
	 * By default: get title
	 * @return string
	 */
	public function getEntityTitle()
	{
		$group = $this->getGroup();
		return isset($group['~NAME'])? $group['~NAME'] : parent::getEntityTitle();
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
		$group = $this->getGroup();
		$photo = (int) $group['IMAGE_ID'];
		$key = $photo . " $width $height";

		if (!isset($this->photoCache[$key]))
		{
			$this->photoCache[$key] = Avatar::getGroup($photo, $width, $height);
		}

		return $this->photoCache[$key];
	}

	/**
	 * Return name of storage.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('DISK_PROXY_TYPE_GROUP_TITLE');
	}

	/**
	 * Return work group params.
	 * @return array|null
	 */
	private function getGroup()
	{
		if ($this->group !== null)
		{
			return $this->group;
		}
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$this->group = \CSocNetGroup::getByID($this->entityId);
		if (!is_array($this->group))
		{
			$this->group = array();
		}

		return $this->group;
	}

	/**
	 * Checks read permissions by user for group.
	 * Uses denormalized rights (b_disk_simple_right).
	 *
	 * Be careful^ this is internal method.
	 * @param mixed $user User.
	 * @param int $groupId Id of group.
	 * @internal
	 * @return bool
	 */
	public static function canRead($user, $groupId)
	{
		$entityType = static::className();
		$groupStorage = Storage::buildFromArray(array(
			'ENTITY_ID' => $groupId,
			'ENTITY_TYPE' => $entityType,
		));
		$proxyType = new static($groupId, $groupStorage);
		$parameters = array(
			'filter' => array(
				'ENTITY_ID' => (int)$groupId,
				'ENTITY_TYPE' => $entityType,
				'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
				'USE_INTERNAL_RIGHTS' => 1,
			),
		);
		$parameters = Driver::getInstance()
			->getRightsManager()
			->addRightsCheck(
				$proxyType->getSecurityContextByUser($user), $parameters, array(
					'ROOT_OBJECT_ID',
					'USE_INTERNAL_RIGHTS'
			)
		);

		return (bool)Storage::getList($parameters)->fetch();
	}


	/**
	 * Checks work group is extranet.
	 * @return bool
	 */
	public function isExtranetGroup()
	{
		if ($group = $this->getGroup())
		{
			if (!isset($this->group['IS_EXTRANET']))
			{
				$this->group['IS_EXTRANET'] = false;
				if (\Bitrix\Main\Loader::includeModule("extranet"))
				{
					$groupId = (int)$group['ID'];
					$this->group['IS_EXTRANET'] = \CExtranet::IsExtranetSocNetGroup($groupId);
				}
			}
		}

		return (bool)$this->group['IS_EXTRANET'];
	}
}