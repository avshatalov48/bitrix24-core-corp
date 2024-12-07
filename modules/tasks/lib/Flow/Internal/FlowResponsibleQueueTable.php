<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class FlowResponsibleQueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowResponsibleQueue_Query query()
 * @method static EO_FlowResponsibleQueue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowResponsibleQueue_Result getById($id)
 * @method static EO_FlowResponsibleQueue_Result getList(array $parameters = [])
 * @method static EO_FlowResponsibleQueue_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowResponsibleQueue_Collection wakeUpCollection($rows)
 */
final class FlowResponsibleQueueTable extends ORM\Data\DataManager
{
	use ORM\Data\Internal\DeleteByFilterTrait;

	public static function getTableName()
	{
		return 'b_tasks_flow_responsible_queue';
	}

	public static function getMap()
	{
		$id = new ORM\Fields\IntegerField('ID');
		$id->configurePrimary();
		$id->configureAutocomplete();

		$flowId = new ORM\Fields\IntegerField('FLOW_ID');
		$flowId->configureRequired();

		$userId = new ORM\Fields\IntegerField('USER_ID');
		$userId->configureRequired();

		$nextUserId = new ORM\Fields\IntegerField('NEXT_USER_ID');
		$nextUserId->configureRequired();

		$sort = new ORM\Fields\IntegerField('SORT');
		$sort->configureDefaultValue(0);

		return [
			$id,
			$flowId,
			$userId,
			$nextUserId,
			$sort,

			(new Reference('FLOW', FlowTable::getEntity(), Join::on('this.FLOW_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_INNER),
		];
	}
}
