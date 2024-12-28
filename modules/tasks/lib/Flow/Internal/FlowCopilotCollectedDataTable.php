<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData;

/**
 * Class FlowCopilotCollectedDataTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowCopilotCollectedData_Query query()
 * @method static EO_FlowCopilotCollectedData_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowCopilotCollectedData_Result getById($id)
 * @method static EO_FlowCopilotCollectedData_Result getList(array $parameters = [])
 * @method static EO_FlowCopilotCollectedData_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotCollectedData wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection wakeUpCollection($rows)
 */
final class FlowCopilotCollectedDataTable extends DataManager
{
	use MergeTrait;
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_flow_copilot_collected_data';
	}

	public static function getObjectClass(): string
	{
		return FlowCopilotCollectedData::class;
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

			(new ArrayField('DATA'))
				->configureRequired()
				->configureSerializationJson(),
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
