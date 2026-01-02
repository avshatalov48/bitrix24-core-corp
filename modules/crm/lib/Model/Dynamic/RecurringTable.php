<?php

namespace Bitrix\Crm\Model\Dynamic;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

class RecurringTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_dynamic_recurring';
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Fields\IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
			(new Fields\IntegerField('ITEM_ID'))
				->configureRequired(),
			new Fields\IntegerField('BASED_ID'),
			(new Fields\BooleanField('ACTIVE'))
				->configureDefaultValue('Y')
				->configureStorageValues('N', 'Y'),
			new Fields\DateField('NEXT_EXECUTION'),
			new Fields\DateField('LAST_EXECUTION'),
			(new Fields\StringField('IS_LIMIT'))
				->configureDefaultValue('N'),
			new Fields\DateField('LIMIT_DATE'),
			new Fields\DateField('START_DATE'),
			new Fields\IntegerField('LIMIT_REPEAT'),
			(new Fields\IntegerField('COUNTER_REPEAT'))
				->configureDefaultValue(0),
			new Fields\IntegerField('CATEGORY_ID'),
			new Fields\TextField('PARAMS', ['serialized' => true]),
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			$fieldRepository
				->getCreatedBy('CREATED_BY_ID')
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,
			$fieldRepository
				->getUpdatedBy('UPDATED_BY_ID')
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,
		];
	}

	public static function deleteByEntityTypeId(int $entityTypeId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(sprintf(
			'DELETE FROM %s WHERE ENTITY_TYPE_ID = %d',
			$helper->quote(static::getTableName()),
			$helper->convertToDbInteger($entityTypeId),
		));
	}
}
