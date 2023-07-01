<?php

namespace Bitrix\Market\Integration\Main;

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Market\Tag\Manager;
use Bitrix\Market\Tag\TagTable;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Main
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'main';
	private const TAG_COUNT_USER = 'user_active_count';
	private const TAG_REGION = 'region';
	private const TAG_COUNT_ADMIN = 'admin_active_count';
	private const TAG_COUNT_INTEGRATOR = 'integrator_active_count';

	/**
	 * Update tag by users active status.
	 *
	 * @param $userFields
	 */
	public static function onAfterUserUpdate($userFields)
	{
		if (isset($userFields['RESULT']))
		{
			$adminCount = static::getAdminCount();
			Manager::saveList(
				[
					[
						'MODULE_ID' => static::MODULE_ID,
						'CODE' => static::TAG_COUNT_USER,
						'VALUE' => UserTable::getActiveUsersCount(),
					],
					[
						'MODULE_ID' => static::MODULE_ID,
						'CODE' => static::TAG_COUNT_ADMIN,
						'VALUE' => $adminCount['ADMIN'],
					],
					[
						'MODULE_ID' => static::MODULE_ID,
						'CODE' => static::TAG_COUNT_INTEGRATOR,
						'VALUE' => $adminCount['INTEGRATOR'],
					]
				]
			);
		}
	}

	/**
	 * Return all tags by module.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		$result = [];
		$adminCount = static::getAdminCount();
		$result[] = [
			'MODULE_ID' => static::MODULE_ID,
			'CODE' => static::TAG_COUNT_USER,
			'VALUE' => UserTable::getActiveUsersCount(),
			'DATE' => null,
			'TYPE' => TagTable::TYPE_DEFAULT,
		];
		$result[] = [
			'MODULE_ID' => static::MODULE_ID,
			'CODE' => static::TAG_REGION,
			'VALUE' => static::getRegion(),
			'DATE' => null,
			'TYPE' => TagTable::TYPE_DEFAULT,
		];
		$result[] = [
			'MODULE_ID' => static::MODULE_ID,
			'CODE' => static::TAG_COUNT_ADMIN,
			'VALUE' => $adminCount['ADMIN'],
			'DATE' => null,
			'TYPE' => TagTable::TYPE_DEFAULT,
		];
		$result[] = [
			'MODULE_ID' => static::MODULE_ID,
			'CODE' => static::TAG_COUNT_INTEGRATOR,
			'VALUE' => $adminCount['INTEGRATOR'],
			'DATE' => null,
			'TYPE' => TagTable::TYPE_DEFAULT,
		];

		return $result;
	}

	private static function getRegion()
	{
		if (Loader::includeModule('bitrix24'))
		{
			$value = \CBitrix24::getLicensePrefix();
		}
		else
		{
			$value = Option::get('main', '~PARAM_CLIENT_LANG', LANGUAGE_ID);
		}

		return $value;
	}

	/**
	 * Returns count of admins and integrators
	 * @return int[]
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function getAdminCount()
	{
		$result = [
			'ADMIN' => 0,
			'INTEGRATOR' => 0,
		];

		$adminGroupId = 1;
		$integratorGroupId = 0;
		if (Loader::includeModule('bitrix24'))
		{
			$integratorGroupId = (int)\Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
		}

		$filter = "U.ACTIVE = 'Y' AND U.LAST_LOGIN IS NOT NULL";
		if ($integratorGroupId > 0)
		{
			$filter .= ' AND UG.GROUP_ID in (' . $adminGroupId . ', ' . $integratorGroupId . ')';
		}
		else
		{
			$filter .= ' AND UG.GROUP_ID = ' . $adminGroupId;
		}

		if (ModuleManager::isModuleInstalled('intranet'))
		{
			$sql = "
				SELECT COUNT(DISTINCT U.ID) as USER_COUNT, UG.GROUP_ID as USER_GROUP
				FROM
					b_user U
					INNER JOIN b_user_group UG ON U.ID = UG.USER_ID
					INNER JOIN b_user_field F ON F.ENTITY_ID = 'USER' AND F.FIELD_NAME = 'UF_DEPARTMENT'
					INNER JOIN b_utm_user UF ON
						UF.FIELD_ID = F.ID
						AND UF.VALUE_ID = U.ID
						AND UF.VALUE_INT > 0
				WHERE {$filter}
				GROUP BY USER_GROUP
			";
		}
		else
		{
			$sql = "
				SELECT COUNT(ID) as USER_COUNT, UG.GROUP_ID as USER_GROUP
				FROM b_user U
				INNER JOIN b_user_group UG ON U.ID = UG.USER_ID
				WHERE {$filter}
				GROUP BY USER_GROUP
			";
		}
		$connection = Application::getConnection();
		$res = $connection->query($sql);
		while ($item = $res->fetch())
		{
			if ((int)$item['USER_GROUP'] === $adminGroupId)
			{
				$result['ADMIN'] = (int)$item['USER_COUNT'];
			}
			elseif ((int)$item['USER_GROUP'] === $integratorGroupId)
			{
				$result['INTEGRATOR'] = (int)$item['USER_COUNT'];
			}
		}

		return $result;
	}

	/**
	 * Handler updates regions tag.
	 * @return null
	 */
	public static function onChangeClientLang()
	{
		Manager::save(
			static::MODULE_ID,
			static::TAG_COUNT_USER,
			static::getRegion()
		);

		return null;
	}

	/**
	 * Handler updates regions tag.
	 * @return null
	 */
	public static function onBitrix24LicenseChange()
	{
		Manager::save(
			static::MODULE_ID,
			static::TAG_COUNT_USER,
			static::getRegion()
		);

		return null;
	}
}
