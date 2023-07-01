<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM\Data\DataManager;

/**
 * Class TraceChannelTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TraceChannel_Query query()
 * @method static EO_TraceChannel_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TraceChannel_Result getById($id)
 * @method static EO_TraceChannel_Result getList(array $parameters = [])
 * @method static EO_TraceChannel_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceChannel createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceChannel_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceChannel wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceChannel_Collection wakeUpCollection($rows)
 */
class TraceChannelTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_trace_channel';
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
			'CODE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'VALUE' => [
				'data_type' => 'string',
			],
			'TRACE' => [
				'data_type' => TraceTable::class,
				'reference' => ['=this.TRACE_ID' => 'ref.ID'],
			],
		];
	}

	/**
	 * Add channel to trace.
	 *
	 * @param int $traceId Trace ID.
	 * @param string $code Channel code.
	 * @param string $value Channel value.
	 * @return bool
	 */
	public static function addChannel($traceId, $code, $value)
	{
		$row = static::getRow([
			'select' => ['ID'],
			'filter' => [
				'=TRACE_ID' => $traceId,
				'=CODE' => $code,
			]
		]);
		if ($row)
		{
			return true;
		}

		return static::add([
			'TRACE_ID' => $traceId,
			'CODE' => $code,
			'VALUE' => $value,
		])->isSuccess();
	}
}