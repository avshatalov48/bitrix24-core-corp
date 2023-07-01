<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main;

/**
 * Class SourceChildTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SourceChild_Query query()
 * @method static EO_SourceChild_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SourceChild_Result getById($id)
 * @method static EO_SourceChild_Result getList(array $parameters = [])
 * @method static EO_SourceChild_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceChild createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceChild_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceChild wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SourceChild_Collection wakeUpCollection($rows)
 */
class SourceChildTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_source_child';
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
			'PARENT_ID' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0,
			],
			'SOURCE_ID' => [
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
			'TITLE' => [
				'data_type' => 'string',
				'required' => true,
			],
			'IS_ENABLED' => [
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 1,
			],
			(new Main\ORM\Fields\Relations\Reference(
				'PARENT',
				static::class,
				Main\ORM\Query\Join::on('this.PARENT_ID', 'ref.ID')
			))->configureJoinType('left'),
			(new Main\ORM\Fields\Relations\Reference(
				'SOURCE',
				SourceTable::class,
				Main\ORM\Query\Join::on('this.SOURCE_ID', 'ref.ID')
			))->configureJoinType('inner'),
			'SOURCE_EXPENSES' => [
				'data_type' => SourceExpensesTable::class,
				'reference' => ['=this.ID' => 'ref.SOURCE_CHILD_ID'],
			],
			'TRACE_SOURCE' => [
				'data_type' => TraceSourceTable::class,
				'reference' => ['=this.ID' => 'ref.SOURCE_CHILD_ID'],
			],
		];
	}
}