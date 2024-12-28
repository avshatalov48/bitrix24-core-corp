<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class CompanyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Company_Query query()
 * @method static EO_Company_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Company_Result getById($id)
 * @method static EO_Company_Result getList(array $parameters = [])
 * @method static EO_Company_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Company createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\CompanyCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Company wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\CompanyCollection wakeUpCollection($rows)
 */
class CompanyTable extends ORM\Data\DataManager
{
	public static function getObjectClass(): string
	{
		return Company::class;
	}

	public static function getCollectionClass(): string
	{
		return CompanyCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_company';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('MY_COMPANY_ID'))
				->configureRequired()
				->configureTitle('B24 company id')
			,
			(new ORM\Fields\StringField('CODE'))
				->configureRequired()
				->configureTitle('External company id')
			,
			(new ORM\Fields\StringField('TITLE'))
				->configureRequired()
				->configureTitle('Company name and reg info')
			,
			(new ORM\Fields\ArrayField('DATA'))
				->configureSerializationJson()
				->configureTitle('Extra data from integration')
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Company created at')
			,
			(new ORM\Fields\Relations\OneToMany(
				'FIELDS',
				FieldTable::class,
				'COMPANY',
			))
			,
			(new ORM\Fields\Relations\OneToMany(
				'PERSONS',
				PersonTable::class,
				'COMPANY',
			))
			,
			(new ORM\Fields\Relations\OneToMany(
				'JOBS',
				JobTable::class,
				'COMPANY',
			))
			,
		];
	}
}