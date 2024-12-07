<?php

namespace Bitrix\Crm\Service\ResponsibleQueue\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;

class QueueTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_responsible_queue';
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
		$map[] = (new IntegerField('CONFIG_ID'))
			->configureRequired()
		;
		$map[] = (new IntegerField('USER_ID'))
			->configureRequired()
		;
		$map[] = (new IntegerField('DEPARTMENT_ID'))
			->configureRequired()
			->configureDefaultValue(100)
		;
		$map[] = $fieldRepository
			->getLastActivityTime('LAST_ACTIVITY_DATE')
		;
		$map[] = (new IntegerField('LAST_ACTIVITY_DATE_EXACT'));
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
