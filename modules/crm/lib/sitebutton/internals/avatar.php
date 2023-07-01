<?
namespace Bitrix\Crm\SiteButton\Internals;

use	Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class AvatarTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Avatar_Query query()
 * @method static EO_Avatar_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Avatar_Result getById($id)
 * @method static EO_Avatar_Result getList(array $parameters = [])
 * @method static EO_Avatar_Entity getEntity()
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Avatar createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Avatar_Collection createCollection()
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Avatar wakeUpObject($row)
 * @method static \Bitrix\Crm\SiteButton\Internals\EO_Avatar_Collection wakeUpCollection($rows)
 */
class AvatarTable extends \Bitrix\Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_button_avatar';
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
			'FILE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			)
		);
	}
}