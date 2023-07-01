<?
namespace Bitrix\Crm\SiteButton\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\DataManager;

Loc::loadMessages(__FILE__);

/**
 * Class GuestEntityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_GuestEntity_Query query()
 * @method static EO_GuestEntity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_GuestEntity_Result getById($id)
 * @method static EO_GuestEntity_Result getList(array $parameters = [])
 * @method static EO_GuestEntity_Entity getEntity()
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_GuestEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_GuestEntity_Collection createCollection()
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_GuestEntity wakeUpObject($row)
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_GuestEntity_Collection wakeUpCollection($rows)
 */
class GuestEntityTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_button_guest_entity';
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
			'GUEST_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			)
		);
	}
}