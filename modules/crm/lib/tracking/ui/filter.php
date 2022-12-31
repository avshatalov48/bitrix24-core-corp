<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\UI;

use Bitrix\Main\Application;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DB;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

/**
 * Class Filter
 *
 * @package Bitrix\Crm\Tracking\UI
 */
class Filter
{
	const SourceId = 'TRACKING_SOURCE_ID';
	const ChannelCode = 'TRACKING_CHANNEL_CODE';
	const DateCreate = 'DATE_CREATE';
	const AssignedById = 'ASSIGNED_BY_ID';

	/**
	 * Append effective filter fields for applying filter from URL.
	 *
	 * @param array &$fields Fields.
	 * @return void
	 */
	public static function appendEffectiveFields(array &$fields)
	{
		if(!in_array(self::SourceId, $fields, true))
		{
			$fields[] = self::SourceId;
		}
		if(!in_array(self::ChannelCode, $fields, true))
		{
			$fields[] = self::ChannelCode;
		}
		if(!in_array(self::DateCreate, $fields, true))
		{
			$fields[] = self::DateCreate;
		}
		if(!in_array(self::AssignedById, $fields, true))
		{
			$fields[] = self::AssignedById;
		}
	}

	/**
	 * Append filter fields.
	 *
	 * @param array &$fields Fields.
	 * @param EntityDataProvider $entityDataProvider Entity filter data provider.
	 * @return void
	 */
	public static function appendFields(array &$fields, EntityDataProvider $entityDataProvider)
	{
		if (Crm\Tracking\Manager::isAccessible())
		{
			$fieldId = self::SourceId;
			$fields[$fieldId] = new Field(
				$entityDataProvider,
				$fieldId,
				[
					'type' => 'list',
					'partial' => true,
					'name' => Loc::getMessage('CRM_TRACKING_UI_FILTER_SOURCE')
				]
			);
		}

		$fieldId = self::ChannelCode;
		$fields[$fieldId] = new Field(
			$entityDataProvider,
			$fieldId,
			[
				'type' => 'list',
				'partial' => true,
				'name' => Loc::getMessage('CRM_TRACKING_UI_FILTER_CHANNEL')
			]
		);
	}

	/**
	 * Return true if filter has field.
	 *
	 * @param string $fieldId Field ID.
	 * @return bool
	 */
	public static function hasField($fieldId)
	{
		return in_array(
			$fieldId,
			self::getFields()
		);
	}

	/**
	 * Returns all available filter fields' codes
	 *
	 * @return string[]
	 */
	public static function getFields(): array
	{
		return [
			self::SourceId,
			self::ChannelCode,
		];
	}

	/**
	 * Return true if has filter field.
	 *
	 * @param string $fieldId Field ID.
	 * @return array
	 */
	public static function getFieldData($fieldId)
	{
		switch ($fieldId)
		{
			case self::SourceId:
				$sources = array_map(
					function ($item)
					{
						if ($item['CODE'] === 'organic')
						{
							$item['ID'] = $item['CODE'];
						}

						return $item;
					},
					Crm\Tracking\Provider::getActualSources()
				);
				return [
					'params' => ['multiple' => 'Y'],
					'items' => array_combine(
						array_column($sources, 'ID'),
						array_column($sources, 'NAME')
					)
				];
			case self::ChannelCode:
				return [
					'params' => ['multiple' => 'Y'],
					'items' => Crm\Tracking\Channel\Factory::getNames()
				];
		}

		return [];
	}

	/**
	 * Append sql to filter builder.
	 *
	 * @param array &$sqlData Sql data.
	 * @param array $filter Filter.
	 * @param int $entityTypeId Entity type ID.
	 * @param string $entitySqlTableAlias Entity sql table alias.
	 * @return void
	 */
	public static function buildFilterAfterPrepareSql(array &$sqlData, array $filter, $entityTypeId, $entitySqlTableAlias = 'L')
	{
		$entityTypeId = (int) $entityTypeId;
		if (!$entityTypeId)
		{
			return;
		}

		if (!isset($filter[self::SourceId]) && !isset($filter[self::ChannelCode]))
		{
			return;
		}

		$sources = isset($filter[self::SourceId]) ?
			implode(
				', ',
				array_map(
					function ($value)
					{
						return (int) $value;
					},
					$filter[self::SourceId]
				)
			)
			:
			'';
		$isNullSource = isset($filter[self::SourceId])
			?
				count(
					array_filter(
						$filter[self::SourceId],
						function ($value)
						{
							return $value === 'organic';
						}
					)
				) > 0
			: false;

		$channels = isset($filter[self::ChannelCode]) ?
			implode(
				', ',
				array_map(
					function ($value)
					{
						return "'" . Application::getConnection()->getSqlHelper()->forSql($value) . "'";
					},
					$filter[self::ChannelCode]
				)
			)
			:
			'';

		$sqlSource = [];
		if (isset($filter[self::SourceId]))
		{
			if ($sources)
			{
				$sqlSource[] = " CTT.SOURCE_ID in ({$sources}) ";
			}
			if ($isNullSource)
			{
				$sqlSource[] = " (CTT.SOURCE_ID is NULL or CTT.SOURCE_ID = 0) ";
			}
		}

		$sqlData['WHERE'][] = "({$entitySqlTableAlias}.ID in (SELECT CTTE.ENTITY_ID FROM "
			. Crm\Tracking\Internals\TraceEntityTable::getTableName() . ' CTTE '
			. ' JOIN ' . Crm\Tracking\Internals\TraceTable::getTableName() . ' CTT ON CTTE.TRACE_ID=CTT.ID '
			. (
				isset($filter[self::ChannelCode])
					? ' JOIN ' . Crm\Tracking\Internals\TraceChannelTable::getTableName() . ' CTTC ON CTTC.TRACE_ID=CTT.ID '
					: ''
			)
			. " WHERE CTTE.ENTITY_TYPE_ID = $entityTypeId "
			. (!empty($sqlSource) ? " AND (" . implode(' OR ', $sqlSource) . ") " : '')
			. (isset($filter[self::ChannelCode]) ? " AND CTTC.CODE in ({$channels}) " : '')
			. ")"
			. ($isNullSource
				?
					" OR NOT EXISTS (SELECT 1 FROM "
					. Crm\Tracking\Internals\TraceEntityTable::getTableName() . " CTTE "
					. " WHERE CTTE.ENTITY_TYPE_ID = $entityTypeId "
					. " AND CTTE.ENTITY_ID = {$entitySqlTableAlias}.ID "
					. ") "
				:
					""
			)
			. ")";
	}

	public static function buildOrmFilter(&$result, array $filter, $entityTypeId, &$runtime)
	{
		//$sqlData = "SELECT 3 where $entityTypeId = 14";
		$sqlData = self::getFilterableSql($filter, $entityTypeId);
		$item = [];

		if (!empty($sqlData['all']))
		{
			$item['@ID'] = new DB\SqlExpression($sqlData['all']);
		}
		if (!empty($sqlData['isNull']))
		{
			$item['!@ID'] = new DB\SqlExpression($sqlData['isNull']);
		}

		if (count($item) > 1)
		{
			$item['LOGIC'] = 'OR';
		}

		if (!empty($item))
		{
			$result[] = $item;
		}
	}

	private static function getFilterableSql(array $filter, $entityTypeId)
	{
		$result = [
			'all' => '',
			'isNull' => ''
		];

		$entityTypeId = (int) $entityTypeId;
		if (!$entityTypeId)
		{
			return $result;
		}

		if (!isset($filter[self::SourceId]) && !isset($filter[self::ChannelCode]))
		{
			return $result;
		}

		$sources = isset($filter[self::SourceId]) ?
			implode(
				', ',
				array_map(
					function ($value)
					{
						return (int) $value;
					},
					$filter[self::SourceId]
				)
			)
			:
			'';
		$isNullSource = isset($filter[self::SourceId])
			?
				count(
					array_filter(
						$filter[self::SourceId],
						function ($value)
						{
							return $value === 'organic';
						}
					)
				) > 0
			: false;

		$channels = isset($filter[self::ChannelCode]) ?
			implode(
				', ',
				array_map(
					function ($value)
					{
						return "'" . Application::getConnection()->getSqlHelper()->forSql($value) . "'";
					},
					$filter[self::ChannelCode]
				)
			)
			:
			'';

		$sqlSource = [];
		if (isset($filter[self::SourceId]))
		{
			if ($sources)
			{
				$sqlSource[] = " CTT.SOURCE_ID in ({$sources}) ";
			}
			if ($isNullSource)
			{
				$sqlSource[] = " (CTT.SOURCE_ID is NULL or CTT.SOURCE_ID = 0) ";
			}
		}

		$result['all'] = "SELECT CTTE.ENTITY_ID FROM "
			. Crm\Tracking\Internals\TraceEntityTable::getTableName() . ' CTTE '
			. ' JOIN ' . Crm\Tracking\Internals\TraceTable::getTableName() . ' CTT ON CTTE.TRACE_ID=CTT.ID '
			. (
				isset($filter[self::ChannelCode])
					? ' JOIN ' . Crm\Tracking\Internals\TraceChannelTable::getTableName() . ' CTTC ON CTTC.TRACE_ID=CTT.ID '
					: ''
			)
			. " WHERE CTTE.ENTITY_TYPE_ID = $entityTypeId "
			. (!empty($sqlSource) ? " AND (" . implode(' OR ', $sqlSource) . ") " : '')
			. (isset($filter[self::ChannelCode]) ? " AND CTTC.CODE in ({$channels}) " : '')
		;

		$result['isNull'] = $isNullSource
			?
				"SELECT CTTE.ENTITY_ID FROM "
				. Crm\Tracking\Internals\TraceEntityTable::getTableName() . " CTTE "
				. " WHERE CTTE.ENTITY_TYPE_ID = $entityTypeId "
			: "";

		return $result;
	}
}