<?php

namespace Bitrix\Crm\Service\Communication\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class CommunicationChannelRuleTable extends \Bitrix\Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_communication_channel_rule';
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
		$map[] = (new IntegerField('CHANNEL_ID'))
			->configureRequired()
		;
		$map[] = (new IntegerField('QUEUE_CONFIG_ID'))
			->configureRequired()
		;
		$map[] = (new IntegerField('SORT'))
			->configureRequired()
			->configureDefaultValue(100)
		;
		$map[] = (new ArrayField('SEARCH_TARGETS'))
			->configureSerializationJson()
		;
		$map[] = (new ArrayField('RULES'))
			->configureRequired()
			->configureSerializationJson()
		;
		$map[] = (new ArrayField('ENTITIES'))
			->configureRequired()
			->configureSerializationJson()
		;
		$map[] = (new ArrayField('SETTINGS'))
			->configureRequired()
			->configureSerializationJson()
		;

		return $map;
	}
}
