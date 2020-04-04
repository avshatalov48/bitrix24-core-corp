<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global $APPLICATION CMain
 */

IncludeModuleLangFile(__FILE__);

/**
 * For internal use only, not public API
 * @access private
 */
class CTasksRarelyTools
{
	public static function onWebdavUninstall()
	{
		global $APPLICATION;

		$APPLICATION->ThrowException(
			GetMessage('TASKS_PREVENT_WEBDAV_UNINSTALL'),
			'TASKS_PREVENT_WEBDAV_UNINSTALL'
		);

		return false;
	}


	public static function onForumUninstall()
	{
		global $APPLICATION;

		$APPLICATION->ThrowException(
			GetMessage('TASKS_PREVENT_FORUM_UNINSTALL'),
			'TASKS_PREVENT_FORUM_UNINSTALL'
		);

		return false;
	}


	public static function onIntranetUninstall()
	{
		global $APPLICATION;

		$APPLICATION->ThrowException(
			GetMessage('TASKS_PREVENT_INTRANET_UNINSTALL'),
			'TASKS_PREVENT_INTRANET_UNINSTALL'
		);

		return false;
	}


	public static function onBeforeUserTypeAdd($arFields)
	{
		/** @var $CACHE_MANAGER CCacheManager */
		global $CACHE_MANAGER;

		if (
			( ! isset($arFields['ENTITY_ID']) )
			|| ($arFields['ENTITY_ID'] === 'TASKS_TASK')
		)
		{
			$CACHE_MANAGER->ClearByTag('tasks_user_fields');
		}
	}


	public static function onBeforeUserTypeUpdate()
	{
		/** @var $CACHE_MANAGER CCacheManager */
		global $CACHE_MANAGER;

		// We can't determine here, is tasks' user field updated or not.
		// So clear cache in any case
		$CACHE_MANAGER->ClearByTag('tasks_user_fields');
	}


	public static function onBeforeUserTypeDelete($arFields)
	{
		/** @var $CACHE_MANAGER CCacheManager */
		global $CACHE_MANAGER;

		if (
			( ! isset($arFields['ENTITY_ID']) )
			|| ($arFields['ENTITY_ID'] === 'TASKS_TASK')
		)
		{
			$CACHE_MANAGER->ClearByTag('tasks_user_fields');
		}
	}


	/**
	 * @return bool true if some mandatory UF exists for TASKS
	 */
	public static function isMandatoryUserFieldExists()
	{
		/** @var $CACHE_MANAGER CCacheManager */
		global $CACHE_MANAGER;

		$isFieldExists = null;		// unknown yet

		$obCache  = new CPHPCache();
		$lifeTime = CTasksTools::CACHE_TTL_UNLIM;
		$cacheID  = md5('uftasks');
		$cacheDir = "/tasks/ufs";

		if (defined('BX_COMP_MANAGED_CACHE') && $obCache->InitCache($lifeTime, $cacheID, $cacheDir))
		{
			$data = $obCache->GetVars();
			$isFieldExists = $data['isFieldExists'];
		}
		else
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$rsUserType = CUserTypeEntity::GetList(
				array(),
				array(
					'ENTITY_ID'  => 'TASKS_TASK',
					'MANDATORY'  => 'Y'
				)
			);

			if ($rsUserType->fetch())
				$isFieldExists = true;
			else
				$isFieldExists = false;

			if (defined('BX_COMP_MANAGED_CACHE') && $obCache->StartDataCache())
			{
				$CACHE_MANAGER->StartTagCache($cacheDir);
				$CACHE_MANAGER->RegisterTag('tasks_user_fields');
				$CACHE_MANAGER->EndTagCache();
				$data = array('isFieldExists' => $isFieldExists);
				$obCache->EndDataCache($data);
			}
		}

		return ($isFieldExists);
	}
}
