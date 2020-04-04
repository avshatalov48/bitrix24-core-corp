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
			'TAGS_RAW' => [
				'data_type' => 'text',
				'serialized' => true,
			],
			'PAGES_RAW' => [
				'data_type' => 'text',
				'serialized' => true,
			],
			'IS_MOBILE' => [
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

		return new ORM\EventResult();
	}
}