<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;

/**
 * Class ScorerTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Scorer_Query query()
 * @method static EO_Scorer_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Scorer_Result getById($id)
 * @method static EO_Scorer_Result getList(array $parameters = [])
 * @method static EO_Scorer_Entity getEntity()
 * @method static \Bitrix\Booking\Internals\Model\EO_Scorer createObject($setDefaultValues = true)
 * @method static \Bitrix\Booking\Internals\Model\EO_Scorer_Collection createCollection()
 * @method static \Bitrix\Booking\Internals\Model\EO_Scorer wakeUpObject($row)
 * @method static \Bitrix\Booking\Internals\Model\EO_Scorer_Collection wakeUpCollection($rows)
 */
class ScorerTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_booking_scorer';
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

			(new IntegerField('USER_ID'))
				->configureRequired(),

			(new IntegerField('ENTITY_ID'))
				->configureRequired(),

			(new StringField('TYPE'))
				->addValidator(new LengthValidator(1, 64))
				->configureRequired(),

			(new IntegerField('VALUE'))
				->configureRequired(),
		];
	}
}
