<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class OptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Option_Query query()
 * @method static EO_Option_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Option_Result getById($id)
 * @method static EO_Option_Result getList(array $parameters = [])
 * @method static EO_Option_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Option createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Option_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Option wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Option_Collection wakeUpCollection($rows)
 */
class OptionTable extends DataManager
{
	use MergeTrait;
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_booking_option';
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
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
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
				]
			),
		];
	}
}
