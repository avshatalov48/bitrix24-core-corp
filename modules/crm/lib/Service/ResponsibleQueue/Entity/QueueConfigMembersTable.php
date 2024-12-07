<?php

namespace Bitrix\Crm\Service\ResponsibleQueue\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class QueueConfigMembersTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_responsible_queue_config_members';
	}

	public static function getMap(): array
	{
		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			$fieldRepository->getId(),
		];
		$map[] = (new IntegerField('SORT'))
			->configureRequired()
			->configureDefaultValue(100)
		;
		$map[] = (new IntegerField('QUEUE_CONFIG_ID'))
			->configureRequired()
		;
		$map[] = (new IntegerField('ENTITY_ID'))
			->configureRequired()
		;
		$map[] = (new StringField('ENTITY_TYPE'))
			->configureRequired()
			->configureSize(255)
		;

		return $map;
	}
}
