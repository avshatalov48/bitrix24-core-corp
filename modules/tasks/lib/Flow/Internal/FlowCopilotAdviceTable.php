<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice;

/**
 * Class FlowCopilotAdviceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowCopilotAdvice_Query query()
 * @method static EO_FlowCopilotAdvice_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowCopilotAdvice_Result getById($id)
 * @method static EO_FlowCopilotAdvice_Result getList(array $parameters = [])
 * @method static EO_FlowCopilotAdvice_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection wakeUpCollection($rows)
 */
final class FlowCopilotAdviceTable extends DataManager
{
	use MergeTrait;
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return FlowCopilotAdvice::class;
	}

	public static function getTableName(): string
	{
		return 'b_tasks_flow_copilot_advice';
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

	public static function getScalarMap(): array
	{
		return [
			(new IntegerField('FLOW_ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new ArrayField('ADVICE'))
				->configureRequired()
				->configureSerializationJson(),

			(new DatetimeField('UPDATED_DATE'))
				->configureDefaultValue(new DateTime())
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
		];
	}
}
