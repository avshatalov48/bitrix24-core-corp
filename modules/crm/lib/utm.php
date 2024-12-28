<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class UtmTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Utm_Query query()
 * @method static EO_Utm_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Utm_Result getById($id)
 * @method static EO_Utm_Result getList(array $parameters = [])
 * @method static EO_Utm_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Utm createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Utm_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Utm wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Utm_Collection wakeUpCollection($rows)
 */
class UtmTable extends Entity\DataManager
{
	private static array $entityUtmCache = [];

	public const ENUM_CODE_UTM_SOURCE = 'UTM_SOURCE';
	public const ENUM_CODE_UTM_MEDIUM = 'UTM_MEDIUM';
	public const ENUM_CODE_UTM_CAMPAIGN = 'UTM_CAMPAIGN';
	public const ENUM_CODE_UTM_CONTENT = 'UTM_CONTENT';
	public const ENUM_CODE_UTM_TERM = 'UTM_TERM';

	public static function getTableName()
	{
		return 'b_crm_utm';
	}

	public static function getMap()
	{
		return [
			'ENTITY_TYPE_ID' => [
				'data_type' => 'integer',
				'primary' => true
			],
			'ENTITY_ID' => [
				'data_type' => 'integer',
				'primary' => true
			],
			'CODE' => [
				'data_type' => 'string',
				'primary' => true
			],
			'VALUE' => [
				'data_type' => 'string'
			]
		];
	}

	public static function getCodeNames()
	{
		return [
			self::ENUM_CODE_UTM_SOURCE => 'UTM Source',
			self::ENUM_CODE_UTM_MEDIUM => 'UTM Medium',
			self::ENUM_CODE_UTM_CAMPAIGN => 'UTM Campaign',
			self::ENUM_CODE_UTM_CONTENT => 'UTM Content',
			self::ENUM_CODE_UTM_TERM => 'UTM Term'
		];
	}

	public static function getCodeList()
	{
		return array_keys(self::getCodeNames());
	}

	protected static function upsertEntityUtmFromFields($isAdd, $entityTypeId, $entityId, $fields)
	{
		$codeList = self::getCodeList();
		$codesToDelete = [];

		foreach ($codeList as $code)
		{
			if (!isset($fields[$code]))
			{
				continue;
			}

			if (!$fields[$code])
			{
				$codesToDelete[] = $code;
			}
		}

		if (!empty($codesToDelete) && !$isAdd)
		{
			static::deleteEntityUtmCodes($entityTypeId, $entityId, $codesToDelete);
		}

		foreach ($codeList as $code)
		{
			if (!isset($fields[$code]))
			{
				continue;
			}

			if (!is_string($fields[$code]))
			{
				continue;
			}

			$primary = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'CODE' => $code
			];

			if (!$isAdd)
			{
				if (static::getRowById($primary))
				{
					$resultDb = static::update($primary, ['VALUE' => $fields[$code]]);
				}
				else
				{
					$isAdd = true;
				}
			}

			if ($isAdd)
			{
				try
				{
					if (static::getRowById($primary))
					{
						$resultDb = static::update($primary, ['VALUE' => $fields[$code]]);
					}
					else
					{
						$addFields = $primary;
						$addFields['VALUE'] = $fields[$code];
						$resultDb = static::add($addFields);
					}
				}
				catch (\Exception)
				{
				}
			}

			if (isset($resultDb))
			{
				$resultDb->isSuccess();
			}
		}

		self::invalidateEntityUtmCache($entityTypeId, $entityId);
	}

	public static function addEntityUtmFromFields($entityTypeId, $entityId, $fields)
	{
		static::upsertEntityUtmFromFields(true, $entityTypeId, $entityId, $fields);
	}

	public static function updateEntityUtmFromFields($entityTypeId, $entityId, $fields)
	{
		static::upsertEntityUtmFromFields(false, $entityTypeId, $entityId, $fields);
	}

	public static function getEntityUtm(int $entityTypeId, int $entityId): array
	{
		$cacheKay = $entityTypeId . '_' . $entityId;

		if (isset(static::$entityUtmCache[$cacheKay]))
		{
			return static::$entityUtmCache[$cacheKay];
		}

		$dbResult = static::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
			]
		]);

		$utm = [];
		while ($row = $dbResult->fetch())
		{
			$utm[$row['CODE']] = $row['VALUE'];
		}

		self::$entityUtmCache[$cacheKay] = $utm;

		return $utm;
	}

	public static function deleteEntityUtm($entityTypeId, $entityId, $code = null)
	{
		$filter = [
			'=ENTITY_TYPE_ID' => $entityTypeId,
			'=ENTITY_ID' => $entityId
		];

		if ($code)
		{
			$filter['=CODE'] = $code;
		}

		$list = static::getList([
			'select' => ['ENTITY_TYPE_ID', 'ENTITY_ID', 'CODE'],
			'filter' => $filter
		]);
		$isSuccess = true;
		foreach ($list as $item)
		{
			$isSuccess = static::delete($item)->isSuccess();
		}

		return $isSuccess;
	}

	public static function deleteEntityUtmCodes($entityTypeId, $entityId, $codes = []): bool
	{
		if (empty($codes))
		{
			return true;
		}

		$filter = [
			'=ENTITY_TYPE_ID' => $entityTypeId,
			'=ENTITY_ID' => $entityId,
			'@CODE' => $codes
		];

		$list = static::getList([
			'select' => ['ENTITY_TYPE_ID', 'ENTITY_ID', 'CODE'],
			'filter' => $filter
		]);
		$isSuccess = true;
		foreach ($list as $item)
		{
			$isSuccess = static::delete($item)->isSuccess();
		}

		return $isSuccess;
	}

	public static function rebind($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		if($oldEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityTypeID');
		}

		if($oldEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'oldEntityID');
		}

		if($newEntityTypeID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityTypeID');
		}

		if($newEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'newEntityID');
		}

		Main\Application::getConnection()->queryExecute(
			"UPDATE b_crm_utm SET ENTITY_TYPE_ID = {$newEntityTypeID}, ENTITY_ID = {$newEntityID} WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);

		self::invalidateEntityUtmCache($oldEntityTypeID, $oldEntityID);
		self::invalidateEntityUtmCache($newEntityTypeID, $newEntityID);
	}
	public static function getUtmFieldsInfo()
	{
		$resultList = [];
		$fieldCodes = self::getCodeList();
		foreach ($fieldCodes as $fieldCode)
		{
			$resultList[$fieldCode] = [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
				],
			];
		}

		return $resultList;
	}

	public static function getFieldsDescriptionByEntityTypeId($entityTypeId, $entitySqlTableAlias = 'L')
	{
		$resultList = [];
		$codeList = self::getCodeList();
		foreach ($codeList as $code)
		{
			$fieldName = mb_strtoupper($code);
			$tableAlias = 'U_' . $fieldName;
			$resultList[$fieldName] = [
				'FIELD' => "{$tableAlias}.VALUE",
				'TYPE' => 'string',
				'FROM' => 'LEFT JOIN ' . self::getTableName() . " {$tableAlias} ON"
					. " {$tableAlias}.ENTITY_TYPE_ID = " . (int) $entityTypeId
					. " AND {$tableAlias}.ENTITY_ID = " . $entitySqlTableAlias . '.ID'
					. " AND {$tableAlias}.CODE = '" . $code . "'"
			];
		}

		return $resultList;
	}

	private static function invalidateEntityUtmCache(int $entityTypeId, int $entityId): void
	{
		$cacheKay = $entityTypeId . '_' . $entityId;
		unset(static::$entityUtmCache[$cacheKay]);
	}
}
