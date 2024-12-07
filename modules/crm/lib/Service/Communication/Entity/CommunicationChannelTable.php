<?php

namespace Bitrix\Crm\Service\Communication\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Query\Join;

class CommunicationChannelTable extends \Bitrix\Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_communication_channel';
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
			->configureSize(128)
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\IntegerField('CATEGORY_ID'))
			->configureRequired()
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\StringField('HANDLER_CLASS'))
			->configureRequired()
			->configureSize(256)
		;
		$map[] = $fieldRepository->getCreatedTime('CREATED_AT', true);
		$map[] = $fieldRepository->getUpdatedTime('UPDATED_AT', true);
		$map[] = $fieldRepository->getCreatedBy('CREATED_BY_ID', true);
		$map[] = $fieldRepository->getUpdatedBy('UPDATED_BY_ID', true);

		$map[] = (new \Bitrix\Main\ORM\Fields\IntegerField('SORT'))
			->configureRequired()
			->configureDefaultValue(100)
		;
		$map[] = (new \Bitrix\Main\ORM\Fields\BooleanField('IS_ENABLED'))
			->configureRequired()
			->configureStorageValues('N', 'Y')
			->configureDefaultValue(true)
		;

		$map[] = (new \Bitrix\Main\ORM\Fields\Relations\Reference(
			'CATEGORY',
			CommunicationCategoryTable::class,
			Join::on('this.CATEGORY_ID', 'ref.ID'),
		));

		return $map;
	}
}