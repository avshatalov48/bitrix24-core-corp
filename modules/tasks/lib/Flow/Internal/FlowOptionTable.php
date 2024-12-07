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
use Bitrix\Tasks\Flow\Internal\Entity\FlowOption;

/**
 * Class FlowOptionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowOption_Query query()
 * @method static EO_FlowOption_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowOption_Result getById($id)
 * @method static EO_FlowOption_Result getList(array $parameters = [])
 * @method static EO_FlowOption_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowOption createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowOption wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowOption_Collection wakeUpCollection($rows)
 */
final class FlowOptionTable extends DataManager
{
	use MergeTrait;
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return FlowOption::class;
	}

	public static function getTableName(): string
	{
		return 'b_tasks_flow_option';
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
		];
	}
}
