<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class ExecutionCountTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_JobCount_Query query()
 * @method static EO_JobCount_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_JobCount_Result getById($id)
 * @method static EO_JobCount_Result getList(array $parameters = [])
 * @method static EO_JobCount_Entity getEntity()
 * @method static \Bitrix\Tasks\Onboarding\Internal\Model\JobCountItem createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Onboarding\Internal\Model\JobCountItemCollection createCollection()
 * @method static \Bitrix\Tasks\Onboarding\Internal\Model\JobCountItem wakeUpObject($row)
 * @method static \Bitrix\Tasks\Onboarding\Internal\Model\JobCountItemCollection wakeUpCollection($rows)
 */
class JobCountTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_onboarding_queue_job_count';
	}

	public static function getObjectClass(): string
	{
		return JobCountItem::class;
	}

	public static function getCollectionClass(): string
	{
		return JobCountItemCollection::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new StringField('CODE'))
				->configureUnique()
				->configureRequired(),

			(new IntegerField('JOB_COUNT'))
				->configureDefaultValue(0),
		];
	}
}