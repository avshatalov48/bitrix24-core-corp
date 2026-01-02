<?php

namespace Bitrix\Crm\RepeatSale\Segment\Entity;

use Bitrix\Crm\Category\Entity\DealCategoryTable;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Text\Emoji;

/**
 * Class RepeatSaleSegmentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RepeatSaleSegment_Query query()
 * @method static EO_RepeatSaleSegment_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RepeatSaleSegment_Result getById($id)
 * @method static EO_RepeatSaleSegment_Result getList(array $parameters = [])
 * @method static EO_RepeatSaleSegment_Entity getEntity()
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegment createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\EO_RepeatSaleSegment_Collection createCollection()
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegment wakeUpObject($row)
 * @method static \Bitrix\Crm\RepeatSale\Segment\Entity\EO_RepeatSaleSegment_Collection wakeUpCollection($rows)
 */
class RepeatSaleSegmentTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_repeat_sale_segment';
	}

	public static function getObjectClass(): string
	{
		return RepeatSaleSegment::class;
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			$fieldRepository->getTitle()
				->configureDefaultValue('')
			,
			(new TextField('PROMPT'))
				->configureRequired()
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode'])
			,
			(new BooleanField('IS_ENABLED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired()
			,
			(new StringField('CODE'))
				->configureSize(30)
				->configureNullable()
			,
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureDefaultValue(\CCrmOwnerType::Deal)
				// @todo temporary support only Deal
				->addValidator(new RangeValidator(\CCrmOwnerType::Deal, \CCrmOwnerType::Deal))
			,
			(new IntegerField('ENTITY_CATEGORY_ID'))
				->configureDefaultValue(0)
				->addValidator(new RangeValidator())
			,
			(new StringField('ENTITY_STAGE_ID'))
				->configureDefaultValue('')
				->configureSize(50)
			,
			(new StringField('ENTITY_TITLE_PATTERN'))
				->configureNullable()
				->configureSize(255)
			,
			(new IntegerField('CALL_ASSESSMENT_ID'))
				->configureNullable()
			,
			(new BooleanField('IS_AI_ENABLED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired()
			,
			(new IntegerField('CLIENT_FOUND'))
				->configureNullable()
				->addValidator(new RangeValidator())
			,
			(new IntegerField('CLIENT_COVERAGE'))
				->configureNullable()
				->addValidator(new RangeValidator(null, 100))
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
				'DEAL_CATEGORY',
				DealCategoryTable::class,
				Join::on('this.ENTITY_CATEGORY_ID', 'ref.ID'),
			),
			new Reference(
				'CALL_ASSESSMENT',
				CopilotCallAssessmentTable::class,
				Join::on('this.CALL_ASSESSMENT_ID', 'ref.ID'),
			),
			(new OneToMany(
				'ASSIGNMENT_USERS',
				RepeatSaleSegmentAssignmentUser::class,
				'SEGMENT',
			))
				->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
