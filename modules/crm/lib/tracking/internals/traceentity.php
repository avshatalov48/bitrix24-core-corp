<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * Class TraceEntityTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TraceEntity_Query query()
 * @method static EO_TraceEntity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TraceEntity_Result getById($id)
 * @method static EO_TraceEntity_Result getList(array $parameters = [])
 * @method static EO_TraceEntity_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceEntity_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceEntity wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceEntity_Collection wakeUpCollection($rows)
 */
class TraceEntityTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_trace_entity';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'TRACE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'ENTITY_TYPE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'ENTITY_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'TRACE' => [
				'data_type' => TraceTable::class,
				'reference' => ['=this.TRACE_ID' => 'ref.ID'],
			],
		];
	}

	/**
	 * Add entity to trace.
	 *
	 * @param int $traceId Trace ID.
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return bool
	 */
	public static function addEntity($traceId, $entityTypeId, $entityId)
	{
		if (!$entityTypeId || !$entityId)
		{
			return false;
		}

		/** @var Main\Orm\Data\DataManager $entityClass */
		$entityClass = null;
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$entityClass = Crm\LeadTable::class;
				break;
			case \CCrmOwnerType::Deal:
				$entityClass = Crm\DealTable::class;
				break;
			case \CCrmOwnerType::Contact:
				$entityClass = Crm\ContactTable::class;
				break;
			case \CCrmOwnerType::Company:
				$entityClass = Crm\CompanyTable::class;
				break;
		}

		if ($entityClass)
		{
			$entityRow = $entityClass::getRow(['select' => ['ID'], 'filter' => ['=ID' => $entityId]]);
			if (!$entityRow)
			{
				return false;
			}
		}

		$tags = [];
		$hasChild = false;
		$rows = TraceTable::getList([
			'select' => [
				'HAS_CHILD',
				'TAGS_RAW',
				'ENTITY_TYPE_ID' => 'ENTITY.ENTITY_TYPE_ID',
				'ENTITY_ID' => 'ENTITY.ENTITY_ID'
			],
			'filter' => ['=ID' => $traceId]
		])->fetchAll();
		foreach ($rows as $row)
		{
			if (empty($tags) && !empty($row['TAGS_RAW']) && is_array($row['TAGS_RAW']))
			{
				$tags = $row['TAGS_RAW'];
			}
			if (!$hasChild && $row['HAS_CHILD'] === 'Y')
			{
				$hasChild = true;
			}

			if ($row['ENTITY_TYPE_ID'] != $entityTypeId)
			{
				continue;
			}

			if ($row['ENTITY_ID'] != $entityId)
			{
				continue;
			}

			return true;
		}

		if ($hasChild)
		{
			$traces = TraceTreeTable::getList([
				'select' => ['CHILD_ID'],
				'filter' => ['=PARENT_ID' => $traceId],
				'limit' => 15
			])->fetchAll();
			foreach (array_column($traces, 'CHILD_ID') as $previousTraceId)
			{
				static::addEntity($previousTraceId, $entityTypeId, $entityId);
			}
		}

		if (!empty($tags))
		{
			$utmRow = UtmTable::getRow([
				'select' => ['CODE'],
				'filter' => [
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ENTITY_ID' => $entityId,
				]
			]);
			if (!$utmRow)
			{
				UtmTable::addEntityUtmFromFields(
					$entityTypeId,
					$entityId,
					array_change_key_case($tags, CASE_UPPER)
				);
			}
		}

		return static::add([
			'TRACE_ID' => $traceId,
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId
		])->isSuccess();
	}

	/**
	 * Remove entity from trace.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return bool
	 */
	public static function removeEntity($entityTypeId, $entityId)
	{
		$row = static::getRowByEntity($entityTypeId, $entityId);
		if (!$row)
		{
			return true;
		}

		$traceId = $row['TRACE_ID'];
		static::delete($row['ID']);
		$row = static::getRow([
			'select' => ['ID'],
			'filter' => ['=TRACE_ID' => $traceId]
		]);
		if ($row)
		{
			return true;
		}

		return TraceTable::delete($traceId)->isSuccess();
	}

	/**
	 * Get row by entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return array|null
	 */
	public static function getRowByEntity($entityTypeId, $entityId)
	{
		return static::getRow([
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId
			],
			'order' => ['ID' => 'DESC']
		]) ?: null;
	}

	/**
	 * Get rows by entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function getRowsByEntity($entityTypeId, $entityId, $limit = 10)
	{
		return static::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId
			],
			'limit' => $limit,
			'order' => ['ID' => 'DESC']
		])->fetchAll();
	}

	/**
	 * Unbind records related to old entity and bind them to new entity.
	 * @param int $oldEntityTypeID Old Entity Type ID.
	 * @param int $oldEntityID Old Entity ID.
	 * @param int $newEntityTypeID New Entity Type ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
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
			"UPDATE b_crm_tracking_trace_entity SET ENTITY_TYPE_ID = {$newEntityTypeID}, ENTITY_ID = {$newEntityID} WHERE ENTITY_TYPE_ID = {$oldEntityTypeID} AND ENTITY_ID = {$oldEntityID}"
		);
	}
}