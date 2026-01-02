<?php

namespace Bitrix\Crm\RepeatSale\Log\Entity;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJobTable;
use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class RepeatSaleLogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RepeatSaleLog_Query query()
 * @method static EO_RepeatSaleLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RepeatSaleLog_Result getById($id)
 * @method static EO_RepeatSaleLog_Result getList(array $parameters = [])
 * @method static EO_RepeatSaleLog_Entity getEntity()
 * @method static \Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\RepeatSale\Log\Entity\EO_RepeatSaleLog_Collection createCollection()
 * @method static \Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLog wakeUpObject($row)
 * @method static \Bitrix\Crm\RepeatSale\Log\Entity\EO_RepeatSaleLog_Collection wakeUpCollection($rows)
 */
class RepeatSaleLogTable extends DataManager
{
	public const REPEAT_SALE_SEGMENT_ID_NAME = 'REPEAT_SALE_SEGMENT_ID';

	public static function getTableName(): string
	{
		return 'b_crm_repeat_sale_log';
	}

	public static function getObjectClass(): string
	{
		return RepeatSaleLog::class;
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
			(new IntegerField('SEGMENT_ID'))
				->configureRequired()
			,
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired()
			,
			(new IntegerField('ENTITY_ID'))
				->configureRequired()
			,
			(new StringField('STAGE_SEMANTIC_ID'))
				->configureSize(1)
				->configureRequired()
				->configureDefaultValue(PhaseSemantics::PROCESS)
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			new Reference(
				'JOB',
				RepeatSaleJobTable::class,
				Join::on('this.JOB_ID', 'ref.ID'),
			),
			new Reference(
				'SEGMENT',
				RepeatSaleSegmentTable::class,
				Join::on('this.SEGMENT_ID', 'ref.ID'),
			),
		];
	}
}
