<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM\Data\DataManager;

/**
 * Class TraceChannelTable
 *
 * @package Bitrix\Crm\Tracking\Internals
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