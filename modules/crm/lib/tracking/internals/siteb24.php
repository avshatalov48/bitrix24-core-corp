<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class SiteB24Table
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SiteB24_Query query()
 * @method static EO_SiteB24_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SiteB24_Result getById($id)
 * @method static EO_SiteB24_Result getList(array $parameters = [])
 * @method static EO_SiteB24_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SiteB24 createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SiteB24_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SiteB24 wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_SiteB24_Collection wakeUpCollection($rows)
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

	/**
	 * Delete by field IS_SHOP.
	 *
	 * @param string $value Value.
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public static function deleteByShopField(string $value)
	{
		$helper = Application::getConnection()->getSqlHelper();
		$tableName = $helper->forSql(static::getTableName());
		$value = $helper->forSql($value);
		$sql = "delete from {$tableName} where IS_SHOP = '$value'";
		Application::getConnection()->query($sql);
	}
}