<?php

namespace Bitrix\Crm\Service\ResponsibleQueue\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\StringField;

class QueueConfigTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_responsible_queue_config';
	}

	public static function getMap(): array
	{
		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			$fieldRepository->getId(),
		];
		$map[] = (new StringField('TITLE'))
			->configureRequired()
			->configureSize(255)
		;
		$map[] = (new StringField('TYPE'))
			->configureRequired()
			->configureSize(64)
		;
		$map[] = $fieldRepository
			->getCreatedTime('CREATED_AT', true)
		;
		$map[] = $fieldRepository
			->getUpdatedTime('UPDATED_AT', true)
		;
		$map[] = (new ArrayField('SETTINGS'))
			->configureRequired()
			->configureSerializationJson()
		;

		return $map;
	}
}
