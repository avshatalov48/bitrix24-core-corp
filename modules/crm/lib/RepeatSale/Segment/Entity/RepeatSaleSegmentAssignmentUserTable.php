<?php

namespace Bitrix\Crm\RepeatSale\Segment\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class RepeatSaleSegmentAssignmentUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RepeatSaleSegmentAssignmentUser_Query query()
 * @method static EO_RepeatSaleSegmentAssignmentUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RepeatSaleSegmentAssignmentUser_Result getById($id)
 * @method static EO_RepeatSaleSegmentAssignmentUser_Result getList(array $parameters = [])
 * @method static EO_RepeatSaleSegmentAssignmentUser_Entity getEntity()
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentAssignmentUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\EO_RepeatSaleSegmentAssignmentUser_Collection createCollection()
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentAssignmentUser wakeUpObject($row)
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\EO_RepeatSaleSegmentAssignmentUser_Collection wakeUpCollection($rows)
 */
class RepeatSaleSegmentAssignmentUserTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_repeat_sale_segment_assignment_user';
	}

	public static function getObjectClass(): string
	{
		return RepeatSaleSegmentAssignmentUserTable::class;
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new IntegerField('SEGMENT_ID'))
				->configureRequired()
				->addValidator(new RangeValidator())
			,
			(new IntegerField('USER_ID'))
				->configureRequired()
				->addValidator(new RangeValidator())
			,
			(new Reference(
				'SEGMENT',
				RepeatSaleSegmentTable::class,
				Join::on('this.SEGMENT_ID', 'ref.ID'),
			))
		];
	}
}
