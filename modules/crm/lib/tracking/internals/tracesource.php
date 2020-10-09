<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM\Data\DataManager;

/**
 * Class TraceSourceTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 */
class TraceSourceTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_trace_source';
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
			'LEVEL' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0,
			],
			'CODE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'SOURCE_CHILD_ID' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0,
			],
			'PROCESSED' => [
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 0,
				'values' => [0, 1]
			],
			'TRACE' => [
				'data_type' => TraceTable::class,
				'reference' => ['=this.TRACE_ID' => 'ref.ID'],
			],
			'SOURCE_CHILD' => [
				'data_type' => SourceChildTable::class,
				'reference' => ['=this.SOURCE_CHILD_ID' => 'ref.ID'],
			],
		];
	}

	/**
	 * Merge data.
	 *
	 * @param array $insert Insert data.
	 * @param array $update Update data.
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	public static function mergeData(array $insert, array $update)
	{
		$entity = static::getEntity();
		$connection = $entity->getConnection();
		$helper = $connection->getSqlHelper();

		$sql = $helper->prepareMerge($entity->getDBTableName(), $entity->getPrimaryArray(), $insert, $update);

		$sql = current($sql);
		if($sql <> '')
		{
			$connection->queryExecute($sql);
			$entity->cleanCache();
		}
	}
}