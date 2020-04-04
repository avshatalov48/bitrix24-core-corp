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