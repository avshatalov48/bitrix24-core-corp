<?php
namespace Bitrix\Disk\ProxyType;


use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\User as UserModel;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

abstract class Disk extends Base
{
	/**
	 * Gets security context (access provider) for user.
	 * Attention! File/Folder can use anywhere and SecurityContext have to check rights anywhere (any module).
	 * @param mixed $user User which use for check rights.
	 * @return SecurityContext
	 */
	public function getSecurityContextByUser($user)
	{
		if($this->isCurrentUser($user))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(Loader::includeModule('socialnetwork') && \CSocnetUser::isCurrentUserModuleAdmin())
			{
				return new FakeSecurityContext($user);
			}

			if(UserModel::isCurrentUserAdmin())
			{
				return new FakeSecurityContext($user);
			}
		}
		else
		{
			$userId = UserModel::resolveUserId($user);
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if($userId && Loader::includeModule('socialnetwork') && \CSocnetUser::isUserModuleAdmin($userId))
			{
				return new FakeSecurityContext($user);
			}
			try
			{
				if(
					$userId &&
					ModuleManager::isModuleInstalled('bitrix24') &&
					Loader::includeModule('bitrix24') &&
					\CBitrix24::isPortalAdmin($userId)
				)
				{
					return new FakeSecurityContext($user);
				}
				elseif($userId)
				{
					//Check user group 1 ('Admins')
					$tmpUser = new \CUser();
					$arGroups = $tmpUser->getUserGroup($userId);
					if(in_array(1, $arGroups))
					{
						return new FakeSecurityContext($user);
					}
				}
			}
			catch(\Exception $e)
			{}
		}

		return new DiskSecurityContext($user);
	}

	/**
	 * Gets url which use for building url to listing folders, trashcan, etc.
	 * @return string
	 */
	public function getStorageBaseUrl()
	{
		return $this->getEntityUrl() . static::SUFFIX_DISK;
	}

	/**
	 * Tells if objects is allowed to index by module "Search".
	 * @return bool
	 */
	public function canIndexBySearch()
	{
		return true;
	}

	/**
	 * Checks $user is current user or not.
	 * @param mixed $user User for check (may be userId, \CAllUser).
	 * @return bool
	 */
	private function isCurrentUser($user)
	{
		global $USER;
		return $USER instanceof \CAllUser && $USER->getId() && $USER->getId() == UserModel::resolveUserId($user);
	}
}