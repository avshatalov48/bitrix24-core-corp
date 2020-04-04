<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Crm\UtmTable;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * Class TraceEntityTable
 *
 * @package Bitrix\Crm\Tracking\Internals
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
		$tags = [];
		$rows = TraceTable::getList([
			'select' => [
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
	 * Remove entity from trace.
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
}