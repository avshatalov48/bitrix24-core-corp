<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\SystemException;

/**
 * Class CounterCalculatedTimeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CounterCalculatedTime_Query query()
 * @method static EO_CounterCalculatedTime_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CounterCalculatedTime_Result getById($id)
 * @method static EO_CounterCalculatedTime_Result getList(array $parameters = [])
 * @method static EO_CounterCalculatedTime_Entity getEntity()
 * @method static \Bitrix\Crm\Counter\EO_CounterCalculatedTime createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Counter\EO_CounterCalculatedTime_Collection createCollection()
 * @method static \Bitrix\Crm\Counter\EO_CounterCalculatedTime wakeUpObject($row)
 * @method static \Bitrix\Crm\Counter\EO_CounterCalculatedTime_Collection wakeUpCollection($rows)
 */
class CounterCalculatedTimeTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_counter_calculated_time';
	}

	public static function getMap(): array
	{
		return  [
			(new IntegerField('USER_ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('CODE'))
				->configurePrimary()
				->addValidator(new Length(1, 255)),
			(new DatetimeField('CALCULATED_AT')),
			(new BooleanField('IS_CALCULATING'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired(),
			(new DatetimeField('CALCULATION_STARTED_AT'))
		];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getCalculatedAt(int $userId, string $code): int
	{
		$result = self::query()
			->setSelect(['CALCULATED_AT'])
			->where('USER_ID', $userId)
			->where( 'CODE',  $code)
			->setCacheTtl(3600)
			->fetch();

		return $result
			? $result['CALCULATED_AT']->getTimestamp()
			: 0;
	}
}
