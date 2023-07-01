<?
namespace Bitrix\Crm\Tracking\Internals;

use Bitrix\Main\Orm;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class SiteTable
 *
 * @package Bitrix\Crm\Tracking\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Site_Query query()
 * @method static EO_Site_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Site_Result getById($id)
 * @method static EO_Site_Result getList(array $parameters = [])
 * @method static EO_Site_Entity getEntity()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Site createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Site_Collection createCollection()
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Site wakeUpObject($row)
 * @method static \Bitrix\Crm\Tracking\Internals\EO_Site_Collection wakeUpCollection($rows)
 */
class SiteTable extends Orm\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_tracking_site';
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
			'HOST' => [
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('CRM_TRACKING_INTERNALS_SITE_TITLE_ADDRESS'),
				'validation' => function ()
				{
					return [
						new Orm\Fields\Validators\UniqueValidator(
							Loc::getMessage('CRM_TRACKING_INTERNALS_SITE_ERROR_UNIQUE_HOST')
						)
					];
				}
			],
			'ADDRESS' => [
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('CRM_TRACKING_INTERNALS_SITE_TITLE_ADDRESS')
			],
			'ACTIVE' => [
				'data_type' => 'string',
			],
			'IS_INSTALLED' => [
				'data_type' => 'string',
			],
			'PHONES' => [
				'data_type' => 'text',
				'serialized' => true,
			],
			'EMAILS' => [
				'data_type' => 'text',
				'serialized' => true,
			],
			'REPLACE_TEXT' => [
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			],
			'ENRICH_TEXT' => [
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			],
			'RESOLVE_DUPLICATES' => [
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			],
		];
	}

	public static function getSiteIdByHost($host)
	{
		static $hosts = null;

		if ($hosts === null)
		{
			$hosts = static::getList([
				'select' => ['ID', 'HOST'],
				'filter' => [
					'=ACTIVE' => 'Y'
				]
			])->fetchAll();
			$hosts = array_combine(
				array_column($hosts, 'HOST'),
				array_column($hosts, 'ID')
			);
		}

		return isset($hosts[$host]) ? $hosts[$host] : null;
	}

	/**
	 * Get host by site ID.
	 *
	 * @param int $siteId Site ID.
	 * @return string|null
	 */
	public static function getHostBySiteId($siteId)
	{
		static $hosts = null;

		if ($hosts === null)
		{
			$hosts = static::getList([
				'select' => ['ID', 'HOST'],
				'filter' => [
					'=ACTIVE' => 'Y'
				]
			])->fetchAll();
			$hosts = array_combine(
				array_column($hosts, 'ID'),
				array_column($hosts, 'HOST')
			);
		}

		return isset($hosts[$siteId]) ? $hosts[$siteId] : null;
	}
}