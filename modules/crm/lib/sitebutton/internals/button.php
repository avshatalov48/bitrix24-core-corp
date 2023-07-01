<?
namespace Bitrix\Crm\SiteButton\Internals;

use	Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Security\Random;

Loc::loadMessages(__FILE__);

/**
 * Class ButtonTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Button_Query query()
 * @method static EO_Button_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Button_Result getById($id)
 * @method static EO_Button_Result getList(array $parameters = [])
 * @method static EO_Button_Entity getEntity()
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Button createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Button_Collection createCollection()
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Button wakeUpObject($row)
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Button_Collection wakeUpCollection($rows)
 */
class ButtonTable extends \Bitrix\Main\Entity\DataManager
{
	const ENUM_LOCATION_TOP_LEFT = 1;
	const ENUM_LOCATION_TOP_MIDDLE = 2;
	const ENUM_LOCATION_TOP_RIGHT = 3;
	const ENUM_LOCATION_BOTTOM_RIGHT = 4;
	const ENUM_LOCATION_BOTTOM_MIDDLE = 5;
	const ENUM_LOCATION_BOTTOM_LEFT = 6;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_button';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'Y',
				'values' => array('N', 'Y')
			),
			'ACTIVE_CHANGE_DATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),
			'ACTIVE_CHANGE_BY' => array(
				'data_type' => 'integer',
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('CRM_BUTTON_FIELD_NAME_TITLE'),
			),
			'BACKGROUND_COLOR' => array(
				'data_type' => 'string',
			),
			'ICON_COLOR' => array(
				'data_type' => 'string',
			),
			'LOCATION' => array(
				'data_type' => 'enum',
				'default_value' => self::ENUM_LOCATION_BOTTOM_RIGHT,
				'values' => array_keys(self::getLocationList())
			),
			'DELAY' => array(
				'data_type' => 'integer',
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
			),
			'ITEMS' => array(
				'data_type' => 'string',
				'required' => true,
				'serialized' => true
			),

			'SETTINGS' => array(
				'data_type' => 'string',
				'serialized' => true
			),

			'SECURITY_CODE' => array(
				'data_type' => 'string',
				'default_value' => function(){
					return Random::getString(6);
				}
			),

			'IS_SYSTEM' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N','Y')
			),

			'XML_ID' => array(
				'data_type' => 'string',
			),
		);
	}

	public static function getLocationCode($id)
	{
		$map = [
			self::ENUM_LOCATION_TOP_LEFT => 'top-left',
			self::ENUM_LOCATION_TOP_MIDDLE => 'top-middle',
			self::ENUM_LOCATION_TOP_RIGHT => 'top-right',
			self::ENUM_LOCATION_BOTTOM_LEFT => 'bottom-left',
			self::ENUM_LOCATION_BOTTOM_MIDDLE => 'bottom-middle',
			self::ENUM_LOCATION_BOTTOM_RIGHT => 'bottom-right',
		];

		return $map[intval($id)];
	}

	public static function getLocationList()
	{
		return array(
			self::ENUM_LOCATION_TOP_LEFT => Loc::getMessage('CRM_BUTTON_LOCATION_TOP_LEFT'),
			self::ENUM_LOCATION_TOP_MIDDLE => Loc::getMessage('CRM_BUTTON_LOCATION_TOP_MIDDLE'),
			self::ENUM_LOCATION_TOP_RIGHT => Loc::getMessage('CRM_BUTTON_LOCATION_TOP_RIGHT'),
			self::ENUM_LOCATION_BOTTOM_LEFT => Loc::getMessage('CRM_BUTTON_LOCATION_BOTTOM_LEFT'),
			self::ENUM_LOCATION_BOTTOM_MIDDLE => Loc::getMessage('CRM_BUTTON_LOCATION_BOTTOM_MIDDLE'),
			self::ENUM_LOCATION_BOTTOM_RIGHT => Loc::getMessage('CRM_BUTTON_LOCATION_BOTTOM_RIGHT'),
		);
	}
}