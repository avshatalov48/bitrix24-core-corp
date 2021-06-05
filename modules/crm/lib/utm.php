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

class UtmTable extends Entity\DataManager
{
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
		foreach ($codeList as $code)
		{
			if (!isset($fields[$code]))
			{
				continue;
			}

			if (!$fields[$code])
			{
				static::deleteEntityUtm($entityTypeId, $entityId, $code);
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
				$addFields = $primary;
				$addFields['VALUE'] = $fields[$code];
				$resultDb = static::add($addFields);
			}

			$resultDb->isSuccess();
		}
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

		return $utm;
	}

	public static function deleteEntityUtm($entityTypeId, $entityId, $code = null)
	{
		$primary = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId
		];

		if ($code)
		{
			$primary['CODE'] = $code;
		}

		$resultDb = static::delete($primary);
		return $resultDb->isSuccess();
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
	}
	public static function getUtmFieldsInfo()
	{
		$resultList = [];
		$fieldCodes = self::getCodeList();
		foreach ($fieldCodes as $fieldCode)
		{
			$resultList[$fieldCode] = [
				'TYPE' => 'string'
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
}
