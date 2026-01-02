<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class CopilotCallAssessmentClientTypeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CopilotCallAssessmentClientType_Query query()
 * @method static EO_CopilotCallAssessmentClientType_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CopilotCallAssessmentClientType_Result getById($id)
 * @method static EO_CopilotCallAssessmentClientType_Result getList(array $parameters = [])
 * @method static EO_CopilotCallAssessmentClientType_Entity getEntity()
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentClientType createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentClientType_Collection createCollection()
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentClientType wakeUpObject($row)
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessmentClientType_Collection wakeUpCollection($rows)
 */
class CopilotCallAssessmentClientTypeTable extends Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_copilot_call_assessment_client_type';
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new Main\ORM\Fields\IntegerField('ASSESSMENT_ID'))
				->configureSize(1)
				->configureRequired()
			,
			(new Main\ORM\Fields\IntegerField('CLIENT_TYPE_ID'))
				->configureSize(1)
				->configureRequired()
			,
			new Reference(
				'ASSESSMENT',
				CopilotCallAssessmentTable::class,
				Join::on('this.ASSESSMENT_ID', 'ref.ID')
			),
		];
	}
}
