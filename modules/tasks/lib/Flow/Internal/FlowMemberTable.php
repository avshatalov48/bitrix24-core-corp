<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMember;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection;
use Bitrix\Tasks\Flow\Internal\Trait\DeleteByFlowIdTrait;

/**
 * Class FlowTaskCreatorTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowMember_Query query()
 * @method static EO_FlowMember_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowMember_Result getById($id)
 * @method static EO_FlowMember_Result getList(array $parameters = [])
 * @method static EO_FlowMember_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowMember createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowMember wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection wakeUpCollection($rows)
 */
class FlowMemberTable extends DataManager
{
	use DeleteByFlowIdTrait;
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return FlowMember::class;
	}

	public static function getCollectionClass(): string
	{
		return FlowMemberCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_tasks_flow_member';
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return array_merge(
			static::getScalarMap(),
			static::getReferenceMap(),
		);
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public static function getScalarMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('FLOW_ID'))
				->configureRequired(),

			(new StringField('ACCESS_CODE'))
				->addValidator(new LengthValidator(1, 100))
				->configureRequired(),

			(new IntegerField('ENTITY_ID'))
				->configureRequired(),

			(new StringField('ENTITY_TYPE'))
				->addValidator(new LengthValidator(1, 100))
				->configureRequired(),

			(new StringField('ROLE'))
				->addValidator(new LengthValidator(1, 2))
				->configureRequired(),
		];
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function getReferenceMap(): array
	{
		return [
			(new Reference('FLOW', FlowTable::getEntity(), Join::on('this.FLOW_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_INNER),
			(new Reference('USER', UserTable::getEntity(), Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', 'U')))
				->configureJoinType(Join::TYPE_INNER),
		];
	}

	/**
	 * @throws ArgumentException
	 */
	public static function deleteByRole(int $flowId, string $role): void
	{
		self::deleteByFilter([
			'=FLOW_ID' => $flowId,
			'=ROLE' => $role,
		]);
	}
}