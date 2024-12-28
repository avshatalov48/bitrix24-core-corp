<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

/**
 * Class FlowUserOptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowUserOption_Query query()
 * @method static EO_FlowUserOption_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowUserOption_Result getById($id)
 * @method static EO_FlowUserOption_Result getList(array $parameters = [])
 * @method static EO_FlowUserOption_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowUserOption_Collection wakeUpCollection($rows)
 */
final class FlowUserOptionTable extends DataManager
{
	use MergeTrait;
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_flow_user_option';
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return array_merge(
			self::getScalarMap(),
			self::getReferenceMap(),
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

			(new IntegerField('USER_ID'))
				->configureRequired(),

			(new StringField('NAME'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 255)),

			(new StringField('VALUE'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 255)),
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

			(new Reference('USER', UserTable::getEntity(), Join::on('this.USER_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_INNER),
		];
	}
}