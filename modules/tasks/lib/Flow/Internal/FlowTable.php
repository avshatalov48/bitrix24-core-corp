<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * Class FlowTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Flow_Query query()
 * @method static EO_Flow_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Flow_Result getById($id)
 * @method static EO_Flow_Result getList(array $parameters = [])
 * @method static EO_Flow_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowEntity wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection wakeUpCollection($rows)
 */
final class FlowTable extends DataManager
{
	public static function getObjectClass(): string
	{
		return FlowEntity::class;
	}

	public static function getCollectionClass(): string
	{
		return FlowEntityCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_tasks_flow';
	}

	public static function getMap(): array
	{
		return array_merge(
			self::getScalarMap(),
			self::getReferenceMap(),
		);
	}

	private static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('CREATOR_ID'))
				->configureRequired(),

			(new IntegerField('OWNER_ID'))
				->configureRequired(),

			(new IntegerField('GROUP_ID'))
				->configureRequired(),

			(new IntegerField('TEMPLATE_ID'))
				->configureDefaultValue(0),

			(new IntegerField('EFFICIENCY'))
				->configureDefaultValue(100),

			(new BooleanField('ACTIVE'))
				->configureValues(0, 1)
				->configureDefaultValue(false),

			(new IntegerField('PLANNED_COMPLETION_TIME'))
				->configureRequired()
				->configureDefaultValue(0),

			(new DatetimeField('ACTIVITY'))
				->configureRequired()
				->configureDefaultValue(new DateTime()),

			(new StringField('NAME'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 255))
				->configureTitle(Loc::getMessage('TASKS_FLOW_FIELD_NAME'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode']),

			(new TextField('DESCRIPTION'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode']),

			(new StringField('DISTRIBUTION_TYPE'))
				->configureRequired(),

			(new BooleanField('DEMO'))
				->configureValues(0, 1)
				->configureDefaultValue(false),
		];
	}

	private static function getReferenceMap(): array
	{
		return [
			(new ManyToMany('TASK', TaskTable::class))
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureTableName(FlowTaskTable::getTableName())
				->configureJoinType(Join::TYPE_INNER),

			(new OneToMany('MEMBERS', FlowMemberTable::class, 'FLOW'))
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureJoinType(Join::TYPE_INNER),

			(new OneToMany('OPTIONS', FlowOptionTable::class, 'FLOW'))
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany('QUEUE', FlowResponsibleQueueTable::class, 'FLOW'))
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
