<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Main\Loader;

class SocialnetworkHandlers
{
	private static $lastGroupIdAddedOnHit;
	private static $lastGroupOwnerIdAddedOnHit;

	/**
	 * @param array $fields
	 * @return void
	 */
	public static function onAfterUserAdd($fields)
	{
		if(!Loader::includeModule('socialnetwork') || empty($fields['ID']))
		{
			return;
		}
		Driver::getInstance()->addUserStorage($fields['ID']);
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	public static function onAfterUserUpdate($fields)
	{
		if(!Loader::includeModule('socialnetwork') || empty($fields['ID']))
		{
			return;
		}

		if(!empty($fields['NAME']) || !empty($fields['LAST_NAME']) || !empty($fields['SECOND_NAME']))
		{
			$user = User::loadById($fields['ID']);
			if (!($user instanceof User))// || $user->isEmptyName())
			{
				return;
			}

			$userName = $user->getFormattedName();
			if (empty($userName))
			{
				return;
			}

			$userStorage = Driver::getInstance()->getStorageByUserId($user->getId());
			if (!($userStorage instanceof Storage))
			{
				return;
			}

			if ($userName != $userStorage->getName())
			{
				$userStorage->rename($userName);
			}
		}
	}

	public static function onUserDelete($userId)
	{
		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			return true;
		}

		try
		{
			$storage->delete(self::getActivityUserId());
		}
		catch(\Exception $e)
		{
			global $APPLICATION;
			if(is_object($APPLICATION))
			{
				$APPLICATION->throwException($e->getMessage());
			}
			return false;
		}

		return true;
	}

	public static function onSocNetGroupAdd($id, $fields)
	{
		self::$lastGroupIdAddedOnHit = $id;
		self::$lastGroupOwnerIdAddedOnHit = !empty($fields['OWNER_ID'])? $fields['OWNER_ID'] : false;
	}

	public static function onSocNetGroupUpdate($groupId, $fields)
	{
		if(empty($fields['NAME']))
		{
			return;
		}

		$storage = Driver::getInstance()->getStorageByGroupId($groupId);
		if(!$storage)
		{
			return;
		}

		$previousName = $storage->getName();
		$correctedPreviousName = Ui\Text::correctFilename($previousName);
		if($previousName === $fields['NAME'])
		{
			return;
		}
		
		if($storage->rename($fields['NAME']))
		{
			foreach($storage->getRootObject()->getSharingsAsReal() as $sharing)
			{
				if(!$sharing->isApproved())
				{
					continue;
				}
				
				$linkObject = $sharing->getLinkObject();
				if(!$linkObject)
				{
					continue;
				}

				if($linkObject->getName() === $previousName || $linkObject->getName() === $correctedPreviousName)
				{
					$linkObject->rename($storage->getName());
				}
			}
			unset($sharing);
		}
	}

	public static function onSocNetGroupDelete($groupId)
	{
		$storage = Driver::getInstance()->getStorageByGroupId($groupId);
		if(!$storage)
		{
			return;
		}
		$storage->delete(self::getActivityUserId());

		RightTable::deleteBatch(array('ACCESS_CODE' => "SG{$groupId}_" . SONET_ROLES_OWNER));
		RightTable::deleteBatch(array('ACCESS_CODE' => "SG{$groupId}_" . SONET_ROLES_MODERATOR));
		RightTable::deleteBatch(array('ACCESS_CODE' => "SG{$groupId}_" . SONET_ROLES_USER));
	}

	public static function onSocNetUserToGroupDelete($id, $fields)
	{
		if(
			isset($fields['ROLE']) &&
			(
				$fields['ROLE'] == SONET_ROLES_USER ||
				$fields['ROLE'] == SONET_ROLES_MODERATOR ||
				$fields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			$userId = $fields['USER_ID'];
			$groupId = $fields['GROUP_ID'];

			if(empty($userId) || empty($groupId))
			{
				return;
			}

			$storage = Driver::getInstance()->getStorageByGroupId($groupId);
			if(!$storage)
			{
				return;
			}
			/** @var Sharing $sharing */
			$sharing = Sharing::load(array(
				'=TO_ENTITY' => Sharing::CODE_USER . $userId,
				'REAL_OBJECT_ID' => $storage->getRootObjectId(),
				'REAL_STORAGE_ID' => $storage->getId(),
			));
			if(!$sharing)
			{
				return;
			}
			$sharing->delete(self::getActivityUserId());
		}
	}

	public static function onSocNetUserToGroupUpdate($id, $fields)
	{
		if(
			isset($fields['ROLE']) &&
			(
				$fields['ROLE'] == SONET_ROLES_USER ||
				$fields['ROLE'] == SONET_ROLES_MODERATOR ||
				$fields['ROLE'] == SONET_ROLES_OWNER
			)
		)
		{
			if(!(isset($fields['USER_ID'])))
			{
				$query = \CSocNetUserToGroup::getList(array(), array('ID' => $id), false, false, array('USER_ID', 'GROUP_ID'));
				if($query)
				{
					$row = $query->fetch();
					if($row)
					{
						$userId = $row['USER_ID'];
						$groupId = $row['GROUP_ID'];
					}
				}
			}
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(!empty($userId) && !empty($groupId) && \CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $groupId, 'files'))
			{
				$storage = Driver::getInstance()->getStorageByGroupId($groupId);
				if(!$storage)
				{
					return;
				}

				$rootObject = $storage->getRootObject();
				if(!$rootObject->canRead($storage->getSecurityContext($userId)))
				{
					return;
				}

				$errorCollection = new ErrorCollection();
				Sharing::connectStorageToUserStorage($userId, $userId, $storage, $errorCollection);
			}
		}
	}

	public static function onSocNetUserToGroupAdd($id, $fields)
	{
		if(
			isset($fields['ROLE']) && isset($fields['USER_ID']) &&
			(
				$fields['ROLE'] == SONET_ROLES_USER ||
				$fields['ROLE'] == SONET_ROLES_MODERATOR ||
				$fields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			if(!(isset($fields['GROUP_ID'])))
			{
				$query = \CSocNetUserToGroup::getList(array(), array('ID' => $id), false, false, array('GROUP_ID', 'INITIATED_BY_USER_ID'));
				if($query)
				{
					$row = $query->fetch();
					if($row)
					{
						$groupId = $row['GROUP_ID'];
					}
				}
			}
			else
			{
				$groupId = $fields['GROUP_ID'];
			}

			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(!empty($groupId) && \CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $groupId, 'files'))
			{
				$storage = Driver::getInstance()->getStorageByGroupId($groupId);
				if(!$storage)
				{
					return;
				}

				$rootObject = $storage->getRootObject();
				if(!$rootObject->canRead($storage->getSecurityContext($fields['USER_ID'])))
				{
					return;
				}

				$errorCollection = new ErrorCollection();
				$createdBy = empty($fields['INITIATED_BY_USER_ID']) ?
					$fields['USER_ID'] : $fields['INITIATED_BY_USER_ID'];

				Sharing::connectStorageToUserStorage($createdBy, $fields['USER_ID'], $storage, $errorCollection);
			}
		}
	}

	public static function onSocNetFeaturesAdd($id, $fields)
	{
		if($fields
		   && isset($fields['ACTIVE'])
		   && $fields['ACTIVE'] == 'Y'
		   && isset($fields['FEATURE'])
		   && $fields['FEATURE'] == 'files'
		   && $fields['ENTITY_TYPE'] == 'G'
		   && $fields['ENTITY_ID']
		)
		{
			$groupId = $fields['ENTITY_ID'];

			$storage = Driver::getInstance()->addGroupStorage($groupId);
			if($storage && self::$lastGroupIdAddedOnHit == $groupId && self::$lastGroupOwnerIdAddedOnHit)
			{
				$rootObject = $storage->getRootObject();
				if(!$rootObject->canRead($storage->getSecurityContext(self::$lastGroupOwnerIdAddedOnHit)))
				{
					return;
				}

				$errorCollection = new ErrorCollection();
				Sharing::connectStorageToUserStorage(
					self::$lastGroupOwnerIdAddedOnHit,
					self::$lastGroupOwnerIdAddedOnHit,
					$storage,
					$errorCollection
				);
			}
		}
	}

	public static function onSocNetFeaturesUpdate($id, $fields)
	{
		static $updateGroupFilesFeatures = false;

		if(!$updateGroupFilesFeatures && isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'N')
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$features = \CSocNetFeatures::getById($id);
			if($features
				&& isset($features['FEATURE'])
				&& $features['FEATURE'] == 'files'
				&& $features['ENTITY_TYPE'] == 'G'
				&& $features['ENTITY_ID']
			)
			{
				$updateGroupFilesFeatures = true;
				$groupId = $features['ENTITY_ID'];

				if(empty($groupId))
				{
					return;
				}

				$storage = Driver::getInstance()->getStorageByGroupId($groupId);
				if(!$storage)
				{
					return;
				}

				$userId = self::getActivityUserId();
				foreach(Sharing::getModelList(array('filter' => array(
						'REAL_OBJECT_ID' => (int)$storage->getRootObjectId(),
						'REAL_STORAGE_ID' => (int)$storage->getId(),
				))) as $sharing)
				{
					$sharing->delete($userId);
				}
				unset($sharing);
			}
		}
		elseif(isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'Y')
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$features = \CSocNetFeatures::getById($id);
			if($features
				&& isset($features['FEATURE'])
				&& $features['FEATURE'] == 'files'
				&& $features['ENTITY_TYPE'] == 'G'
				&& $features['ENTITY_ID']
			)
			{
				$groupId = $features['ENTITY_ID'];
				if(!empty($groupId))
				{
					Driver::getInstance()->addGroupStorage($groupId);
				}
			}
		}
	}

	public static function onAfterFetchDiskUfEntity(array $entities)
	{
		foreach($entities as $name => $ids)
		{
			if($name === 'BLOG_POST')
			{
				if(is_array($ids))
				{
					Driver::getInstance()->getUserFieldManager()->loadBatchAttachedObjectInBlogPost($ids);
				}
			}
		}
		unset($name);
	}

	private static function getActivityUserId()
	{
		global $USER;
		if($USER && $USER instanceof \CUser)
		{
			$userId = $USER->getId();
			if(is_numeric($userId) && ((int)$userId > 0))
			{
				return $userId;
			}
		}

		return SystemUser::SYSTEM_USER_ID;
	}
}