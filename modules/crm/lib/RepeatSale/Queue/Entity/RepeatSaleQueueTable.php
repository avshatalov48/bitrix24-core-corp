<?php

namespace Bitrix\Crm\RepeatSale\Queue\Entity;

use Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJobTable;
use Bitrix\Crm\RepeatSale\Queue\Status;
use Bitrix\Crm\RepeatSale\Service\Handler\HandlerType;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class RepeatSaleQueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RepeatSaleQueue_Query query()
 * @method static EO_RepeatSaleQueue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RepeatSaleQueue_Result getById($id)
 * @method static EO_RepeatSaleQueue_Result getList(array $parameters = [])
 * @method static EO_RepeatSaleQueue_Entity getEntity()
 * @method static \Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\RepeatSale\Queue\Entity\EO_RepeatSaleQueue_Collection createCollection()
 * @method static \Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueue wakeUpObject($row)
 * @method static \Bitrix\Crm\RepeatSale\Queue\Entity\EO_RepeatSaleQueue_Collection wakeUpCollection($rows)
 */
class RepeatSaleQueueTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_repeat_sale_queue';
	}

	public static function getObjectClass(): string
	{
		return RepeatSaleQueue::class;
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new IntegerField('JOB_ID'))
				->configureRequired()
			,
			(new BooleanField('IS_ONLY_CALC'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureRequired()
			,
			(new IntegerField('STATUS'))
				->configureRequired()
				->configureDefaultValue(Status::Waiting->value)
				->addValidator(new RangeValidator(Status::Waiting->value, Status::Progress->value))
			,
			(new IntegerField('LAST_ENTITY_TYPE_ID'))
				->configureNullable()
			,
			(new IntegerField('LAST_ITEM_ID'))
				->configureNullable()
			,
			(new IntegerField('LAST_ASSIGNMENT_ID'))
				->configureNullable()
			,
			(new IntegerField('ITEMS_COUNT'))
				->configureRequired()
				->addValidator(new RangeValidator())
				->configureDefaultValue(0)
			,
			(new IntegerField('HANDLER_TYPE_ID'))
				->configureRequired()
				->addValidator(new RangeValidator(HandlerType::SystemHandler->value, HandlerType::ConfigurableHandler->value))
				->configureDefaultValue(HandlerType::SystemHandler->value)
			,
			(new IntegerField('RETRY_COUNT'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new StringField('HASH'))
				->configureSize(32)
				->configureNullable()
				->addValidator(new LengthValidator(32, 32))
			,
			(new ArrayField('PARAMS'))
				->configureNullable()
				->configureSerializationJson()
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			new Reference(
				'JOB',
				RepeatSaleJobTable::class,
				Join::on('this.JOB_ID', 'ref.ID'),
			),
		];
	}
}
