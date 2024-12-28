<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Class PersonTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Person_Query query()
 * @method static EO_Person_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Person_Result getById($id)
 * @method static EO_Person_Result getList(array $parameters = [])
 * @method static EO_Person_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Person createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\PersonCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Person wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\PersonCollection wakeUpCollection($rows)
 */
class PersonTable extends ORM\Data\DataManager
{
	use  DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return Person::class;
	}

	public static function getCollectionClass(): string
	{
		return PersonCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_person';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('COMPANY_ID'))
				->configureRequired()
				->configureTitle('Company id')
			,
			(new ORM\Fields\Relations\Reference(
				'COMPANY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID')
			))
			,
			(new ORM\Fields\IntegerField('USER_ID'))
				->configureTitle('Mapped user id')
			,
			(new ORM\Fields\IntegerField('MATCH_COUNTER'))
				->configureDefaultValue(0)
				->configureTitle('Counter for show order')
			,
			(new ORM\Fields\StringField('CODE'))
				->configureRequired()
				->configureTitle('External member id')
			,
			(new ORM\Fields\StringField('TITLE'))
				->configureRequired()
				->configureTitle('Person info')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Person created at')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Person updated at')
			,
			(new ORM\Fields\Relations\OneToMany(
				'EMPLOYEES',
				EmployeeTable::class,
				'PERSON',
			))
			,
		];
	}
}