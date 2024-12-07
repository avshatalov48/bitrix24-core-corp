<?php
namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class OptionTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> VALUE string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Option_Query query()
 * @method static EO_Option_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Option_Result getById($id)
 * @method static EO_Option_Result getList(array $parameters = [])
 * @method static EO_Option_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\Option createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\EO_Option_Collection createCollection()
 * @method static \Bitrix\StaffTrack\Model\Option wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\EO_Option_Collection wakeUpCollection($rows)
 */

class OptionTable extends DataManager
{
	use MergeTrait;
	use DeleteByFilterTrait;

	/**
	 * @return string
	 */
	public static function getObjectClass()
	{
		return Option::class;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_stafftrack_option';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('OPTION_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('OPTION_ENTITY_USER_ID_FIELD'),
				]
			),
			new StringField(
				'NAME',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 255),
						];
					},
					'title' => Loc::getMessage('OPTION_ENTITY_NAME_FIELD'),
				]
			),
			new StringField(
				'VALUE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 255),
						];
					},
					'title' => Loc::getMessage('OPTION_ENTITY_VALUE_FIELD'),
				]
			),
		];
	}
}