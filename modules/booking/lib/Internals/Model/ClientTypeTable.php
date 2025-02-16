<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;

/**
 * Class ClientTypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ClientType_Query query()
 * @method static EO_ClientType_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ClientType_Result getById($id)
 * @method static EO_ClientType_Result getList(array $parameters = [])
 * @method static EO_ClientType_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_ClientType createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_ClientType_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_ClientType wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_ClientType_Collection wakeUpCollection($rows)
 */
final class ClientTypeTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_booking_client_type';
	}

	/**
	 * @throws ArgumentTypeException|SystemException
	 */
	public static function getMap(): array
	{
		return array_merge(
			static::getScalarMap(),
		);
	}

	/**
	 * @throws ArgumentTypeException|SystemException
	 */
	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new StringField('MODULE_ID'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),

			(new StringField('CODE'))
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),
		];
	}
}
