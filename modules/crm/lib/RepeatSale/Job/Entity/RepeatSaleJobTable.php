<?php

namespace Bitrix\Crm\RepeatSale\Job\Entity;

use Bitrix\Crm\RepeatSale\Schedule\ScheduleType;
use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class RepeatSaleJobTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RepeatSaleJob_Query query()
 * @method static EO_RepeatSaleJob_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RepeatSaleJob_Result getById($id)
 * @method static EO_RepeatSaleJob_Result getList(array $parameters = [])
 * @method static EO_RepeatSaleJob_Entity getEntity()
 * @method static \Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJob createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\RepeatSale\Job\Entity\EO_RepeatSaleJob_Collection createCollection()
 * @method static \Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJob wakeUpObject($row)
 * @method static \Bitrix\Crm\RepeatSale\Job\Entity\EO_RepeatSaleJob_Collection wakeUpCollection($rows)
 */
class RepeatSaleJobTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_repeat_sale_job';
	}

	public static function getObjectClass(): string
	{
		return RepeatSaleJob::class;
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new IntegerField('SEGMENT_ID'))
				->configureRequired()
			,
			(new IntegerField('SCHEDULE_TYPE'))
				->configureRequired()
				->addValidator(new RangeValidator(ScheduleType::Regular->value, ScheduleType::Once->value))
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			$fieldRepository
				->getCreatedBy('CREATED_BY_ID')
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,
			$fieldRepository
				->getUpdatedBy('UPDATED_BY_ID')
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,
			new Reference(
				'SEGMENT',
				RepeatSaleSegmentTable::class,
				Join::on('this.SEGMENT_ID', 'ref.ID'),
			),
		];
	}
}
