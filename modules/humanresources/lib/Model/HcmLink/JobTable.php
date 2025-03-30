<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

/**
 * Class JobTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Job_Query query()
 * @method static EO_Job_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Job_Result getById($id)
 * @method static EO_Job_Result getList(array $parameters = [])
 * @method static EO_Job_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Job createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\JobCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Job wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\JobCollection wakeUpCollection($rows)
 */
class JobTable extends ORM\Data\DataManager
{
	use ORM\Data\Internal\DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return Job::class;
	}

	public static function getCollectionClass(): string
	{
		return JobCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_job';
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
				->configureTitle('B24 company id')
			,
			(new ORM\Fields\Relations\Reference(
				'COMPANY',
				CompanyTable::class,
				ORM\Query\Join::on('this.COMPANY_ID', 'ref.ID')
			))
			,
			(new ORM\Fields\IntegerField('TYPE'))
				->configureTitle('Type')
			,
			(new ORM\Fields\IntegerField('STATUS'))
				->configureTitle('Status')
			,
			(new ORM\Fields\IntegerField('PROGRESS_RECEIVED'))
				->configureTitle('Num of send records')
			,
			(new ORM\Fields\IntegerField('PROGRESS_TOTAL'))
				->configureTitle('Num of all records')
			,
			(new ORM\Fields\ArrayField('INPUT_DATA'))
				->configureSerializationJson()
				->configureTitle('Event data for external call')
			,
			(new ORM\Fields\ArrayField('OUTPUT_DATA'))
				->configureSerializationJson()
				->configureTitle('Event data for external call')
			,
			(new ORM\Fields\IntegerField('EVENT_COUNT'))
				->configureTitle('Event count')
				->configureDefaultValue(0)
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Job created at')
			,
			(new ORM\Fields\DatetimeField('UPDATED_AT'))
				->configureDefaultValue(new DateTime())
				->configureTitle('Job updated at')
			,
			(new ORM\Fields\DatetimeField('FINISHED_AT'))
				->configureTitle('Job finished at')
			,

		];
	}
}