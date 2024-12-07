<?php

namespace Bitrix\Crm\Service\Communication\Entity;

use Bitrix\Main\DI\ServiceLocator;

class CommunicationChannelEventTable extends \Bitrix\Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_communication_channel_event';
	}

	public static function getMap(): array
	{
		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			$fieldRepository->getId(),
		];
		$map[] = (new \Bitrix\Main\ORM\Fields\StringField('MODULE_ID'))
			->configureRequired()
			->configureSize(64)
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\StringField('EVENT_ID'))
			->configureRequired()
			->configureSize(255)
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\IntegerField('USER_ID'));
		$map[] = (new \Bitrix\Main\ORM\Fields\ArrayField('DATA'))
			->configureRequired()
			->configureSerializationJson()
		;
		$map[] = $fieldRepository->getCreatedTime('CREATED_AT', true);
		$map[] = $fieldRepository->getUpdatedTime('UPDATED_AT', true);

		return $map;
	}
}