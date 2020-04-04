<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class TraceTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 */
class TraceTable extends ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_trace';
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
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			],
			'SOURCE_ID' => [
				'data_type' => 'integer',
				'default_value' => 0
			],
			'GUEST_ID' => [
				'data_type' => 'integer',
			],

			(new ORM\Fields\ArrayField('TAGS_RAW'))->configureSerializationPhp(),

			(new ORM\Fields\ArrayField('PAGES_RAW'))->configureSerializationPhp(),

			'IS_MOBILE' => [
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			],
			'HAS_CHILD' => [
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			],
			'SOURCE' => [
				'data_type' => SourceTable::class,
				'reference' => ['=this.SOURCE_ID' => 'ref.ID'],
			],
			'CHANNEL' => array(
				'data_type' => TraceChannelTable::class,
				'reference' => array('=this.ID' => 'ref.TRACE_ID'),
			),
			'ENTITY' => array(
				'data_type' => TraceEntityTable::class,
				'reference' => array('=this.ID' => 'ref.TRACE_ID'),
			),
		];
	}

	/**
	 * On delete event handler.
	 *
	 * @param ORM\Event $event Event.
	 * @return ORM\EventResult
	 */
	public static function onDelete(ORM\Event $event)
	{
		$data = $event->getParameters();
		$traceId = $data['primary']['ID'];

		$entities = TraceEntityTable::getList([
			'select' => ['ID'],
			'filter' => ['=TRACE_ID' => $traceId]
		]);
		while ($row = $entities->fetch())
		{
			TraceEntityTable::delete($row['ID']);
		}

		$entities = TraceChannelTable::getList([
			'select' => ['ID'],
			'filter' => ['=TRACE_ID' => $traceId]
		]);
		while ($row = $entities->fetch())
		{
			TraceChannelTable::delete($row['ID']);
		}

		$childTraces = TraceTreeTable::getList([
			'select' => ['ID'],
			'filter' => ['=PARENT_ID' => $traceId]
		]);
		while ($row = $childTraces->fetch())
		{
			TraceTreeTable::delete($row['ID']);
		}

		return new ORM\EventResult();
	}

	/**
	 * Get trace ID by entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return array|null
	 */
	public static function getTraceIdByEntity($entityTypeId, $entityId)
	{
		$row = TraceEntityTable::getRow([
			'select' => ['TRACKING_TRACE_ID' => 'TRACE.ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId
			],
			'order' => ['ID' => 'DESC']
		]);

		return !empty($row['TRACKING_TRACE_ID']) ? $row['TRACKING_TRACE_ID'] : null;
	}

	/**
	 * Get trace by entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return array|null
	 */
	public static function getTraceByEntity($entityTypeId, $entityId)
	{
		$row = TraceEntityTable::getRow([
			'select' => ['TRACE.*'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId
			],
			'order' => ['ID' => 'DESC']
		]);

		return $row ? $row : null;
	}

	/**
	 * Get spare(without entities) trace ID by channel.
	 *
	 * @param string $channelCode Channel code.
	 * @param string $channelValue Channel value.
	 * @param DateTime $dateCreateFrom Date create from.
	 * @return int|null
	 */
	public static function getSpareTraceIdByChannel($channelCode, $channelValue, DateTime $dateCreateFrom)
	{
		$row = static::getList([
			'select' => ['ID'],
			'filter' => [
				'>DATE_CREATE' => $dateCreateFrom,
				'=ENTITY.ID' => null,
				'=CHANNEL.CODE' => $channelCode,
				'=CHANNEL.VALUE' => $channelValue,
			],
			'order' => ['ID' => 'ASC']
		])->fetch();

		return $row ? $row['ID'] : null;
	}
}