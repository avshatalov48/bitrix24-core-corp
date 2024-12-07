<?php

namespace Bitrix\Tasks\Flow\Internal;

use Bitrix\Main\ORM;
use Bitrix\Tasks\Internals\UpdateByFilterTrait;

/**
 * Class FlowNotificationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FlowNotification_Query query()
 * @method static EO_FlowNotification_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FlowNotification_Result getById($id)
 * @method static EO_FlowNotification_Result getList(array $parameters = [])
 * @method static EO_FlowNotification_Entity getEntity()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowNotification createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection createCollection()
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowNotification wakeUpObject($row)
 * @method static \Bitrix\Tasks\Flow\Internal\EO_FlowNotification_Collection wakeUpCollection($rows)
 */
final class FlowNotificationTable extends ORM\Data\DataManager
{
	use ORM\Data\Internal\DeleteByFilterTrait;
	use UpdateByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_tasks_flow_notification';
	}

	public static function getMap(): array
	{
		$id = (new ORM\Fields\IntegerField('ID'))
			->configurePrimary()
			->configureAutocomplete()
		;

		$flowId = (new ORM\Fields\IntegerField('FLOW_ID'))
			->configureRequired()
		;

		$integrationId = (new ORM\Fields\IntegerField('INTEGRATION_ID'))
			->configureRequired()
		;

		$status = (new ORM\Fields\StringField('STATUS'))
			->configureRequired()
			->addValidator(new ORM\Fields\Validators\LengthValidator(null, 50))
		;

		$data = (new ORM\Fields\StringField('DATA'))
			->configureRequired()
		;

		return [
			$id,
			$flowId,
			$integrationId,
			$status,
			$data
		];
	}
}
