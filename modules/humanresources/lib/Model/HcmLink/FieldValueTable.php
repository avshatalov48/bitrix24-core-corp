<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\Type\DateTime;

/**
 * Class FieldValueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldValue_Query query()
 * @method static EO_FieldValue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldValue_Result getById($id)
 * @method static EO_FieldValue_Result getList(array $parameters = [])
 * @method static EO_FieldValue_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldValue createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldValue wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldValueCollection wakeUpCollection($rows)
 */
class FieldValueTable extends ORM\Data\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return FieldValue::class;
	}

	public static function getCollectionClass(): string
	{
		return FieldValueCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_field_value';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('EMPLOYEE_ID'))
				->configureRequired()
				->configureTitle('employee ID')
			,
			(new ORM\Fields\Relations\Reference(
				'EMPLOYEE',
				EmployeeTable::class,
				ORM\Query\Join::on('this.EMPLOYEE_ID', 'ref.ID')
			))
			,
			(new ORM\Fields\IntegerField('FIELD_ID'))
				->configureRequired()
				->configureTitle('field id')
			,
			(new ORM\Fields\Relations\Reference(
				'FIELD',
				FieldTable::class,
				ORM\Query\Join::on('this.FIELD_ID', 'ref.ID')
			))
			,
			(new ORM\Fields\StringField('VALUE'))
				->configureTitle('Value')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Created at')
			,
			(new ORM\Fields\DatetimeField('EXPIRED_AT'))
				->configureTitle('Expired at')
			,
		];
	}
}