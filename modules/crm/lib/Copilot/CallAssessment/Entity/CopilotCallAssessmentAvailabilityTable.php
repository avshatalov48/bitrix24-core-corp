<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Entity;

use Bitrix\Crm\Copilot\CallAssessment\Enum\AvailabilityWeekdayType;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class CopilotCallAssessmentAvailabilityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CopilotCallAssessmentAvailability_Query query()
 * @method static EO_CopilotCallAssessmentAvailability_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CopilotCallAssessmentAvailability_Result getById($id)
 * @method static EO_CopilotCallAssessmentAvailability_Result getList(array $parameters = [])
 * @method static EO_CopilotCallAssessmentAvailability_Entity getEntity()
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentAvailability createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentAvailability_Collection createCollection()
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentAvailability wakeUpObject($row)
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentAvailability_Collection wakeUpCollection($rows)
 */
class CopilotCallAssessmentAvailabilityTable extends Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_copilot_call_assessment_availability';
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new Main\ORM\Fields\IntegerField('ASSESSMENT_ID'))
				->configureRequired()
			,
			(new Main\ORM\Fields\DatetimeField('START_POINT'))
				->configureRequired()
			,
			(new Main\ORM\Fields\DatetimeField('END_POINT'))
				->configureRequired()
			,
			(new Main\ORM\Fields\EnumField('WEEKDAY_TYPE'))
				->configureNullable()
				->configureValues(AvailabilityWeekdayType::values())
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			new Reference(
				'ASSESSMENT',
				CopilotCallAssessmentTable::class,
				Join::on('this.ASSESSMENT_ID', 'ref.ID')
			),
		];
	}

	public static function onBeforeAdd(Event $event): EventResult
	{
		return static::check($event);
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		return static::check($event);
	}

	private static function check(Event $event): EventResult
	{
		$result = new EventResult();
		$fields = $event->getParameter('fields');
		if (
			isset($fields['START_POINT'], $fields['END_POINT'])
			&& $fields['START_POINT'] > $fields['END_POINT']
		)
		{
			$result->addError(new EntityError('Start datetime cannot be greater than end datetime'));
		}

		return $result;
	}
}
