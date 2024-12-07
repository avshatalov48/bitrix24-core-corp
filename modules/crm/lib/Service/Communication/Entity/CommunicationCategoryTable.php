<?php

namespace Bitrix\Crm\Service\Communication\Entity;

use Bitrix\Main\DI\ServiceLocator;

class CommunicationCategoryTable extends \Bitrix\Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_communication_category';
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
		$map[] = (new \Bitrix\Main\ORM\Fields\StringField('CODE'))
			->configureRequired()
			->configureUnique()
			->configureSize(50)
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\IntegerField('SORT'))
			->configureDefaultValue(100)
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\StringField('HANDLER_CLASS'))
			->configureRequired()
			->configureSize(255)
		;

		return $map;
	}
}
