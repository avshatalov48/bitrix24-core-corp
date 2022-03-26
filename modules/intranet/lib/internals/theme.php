<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2021 Bitrix
 */
namespace Bitrix\Intranet\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;

/**
 * Class ThemeTable
 *
 * @package Bitrix\Intranet\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Theme_Query query()
 * @method static EO_Theme_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Theme_Result getById($id)
 * @method static EO_Theme_Result getList(array $parameters = array())
 * @method static EO_Theme_Entity getEntity()
 * @method static \Bitrix\Intranet\Internals\EO_Theme createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\Internals\EO_Theme_Collection createCollection()
 * @method static \Bitrix\Intranet\Internals\EO_Theme wakeUpObject($row)
 * @method static \Bitrix\Intranet\Internals\EO_Theme_Collection wakeUpCollection($rows)
 */
class ThemeTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_intranet_theme';
	}

	/**
	 * Get map.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'THEME_ID' => [
				'data_type' => 'string',
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'ENTITY_TYPE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'ENTITY_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'CONTEXT' => [
				'data_type' => 'string',
				'required' => true,
			],
		];
	}

	public static function set(array $params = []): bool
	{
		$themeId = (string)($params['THEME_ID'] ?? '');
		$userId = (int)($params['USER_ID'] ?? 0);
		$entityType = (string)($params['ENTITY_TYPE'] ?? '');
		$entityId = (int)($params['ENTITY_ID'] ?? 0);
		$context = (string)($params['CONTEXT'] ?? '');

		if (
			empty($themeId)
			|| empty($entityType)
		)
		{
			return false;
		}

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$insertFields = [
			'THEME_ID' => $helper->forSql($themeId),
			'USER_ID' => $userId,
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
			'CONTEXT' => $context,
		];

		$updateFields = [
			'THEME_ID' => $helper->forSql($themeId),
			'USER_ID' => $userId,
		];

		$mergeQuery = $helper->prepareMerge(
			static::getTableName(),
			[ 'ENTITY_TYPE', 'ENTITY_ID', 'CONTEXT' ],
			$insertFields,
			$updateFields
		);

		if ($mergeQuery[0] !== '')
		{
			$connection->query($mergeQuery[0]);
			self::getEntity()->cleanCache();
		}

		return true;
	}
}
