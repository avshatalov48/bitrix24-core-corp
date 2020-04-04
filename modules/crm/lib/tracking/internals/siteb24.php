<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class SiteB24Table
 *
 * @package Bitrix\Crm\Tracking\Internals
 */
class SiteB24Table extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_site_b24';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'LANDING_SITE_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'IS_SHOP' => [
				'primary' => true,
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			],

		];
	}
}