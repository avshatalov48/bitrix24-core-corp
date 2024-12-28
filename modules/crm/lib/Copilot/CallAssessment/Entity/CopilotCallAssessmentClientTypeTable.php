<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

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
			(new Reference(
				'ASSESSMENT',
				CopilotCallAssessmentTable::class,
				Join::on('this.ASSESSMENT_ID', 'ref.ID')
			))
			,
		];
	}
}