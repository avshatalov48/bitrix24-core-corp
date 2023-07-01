<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\Orm;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class TraceTreeTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TraceTree_Query query()
 * @method static EO_TraceTree_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TraceTree_Result getById($id)
 * @method static EO_TraceTree_Result getList(array $parameters = [])
 * @method static EO_TraceTree_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceTree createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceTree_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceTree wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_TraceTree_Collection wakeUpCollection($rows)
 */
class TraceTreeTable extends Orm\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_trace_tree';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			(new Orm\Fields\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary()
			,
			(new Orm\Fields\IntegerField('CHILD_ID'))
				->configureRequired()
			,
			(new Orm\Fields\IntegerField('PARENT_ID'))
				->configureRequired()
			,
		];
	}
}