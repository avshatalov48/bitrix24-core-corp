<?php

namespace Bitrix\Crm\Service\Logger\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ArrayField;

class LogTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_log';
	}

	public static function getMap(): array
	{
		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new StringField('LOGGER_ID'))
				->configureRequired()
				->configureSize(100)
			,
			$fieldRepository->getCreatedTime('CREATED_TIME'),
			(new DatetimeField('VALID_TO'))
				->configureRequired(),
			(new StringField('LOG_LEVEL'))
				->configureRequired()
				->configureSize(32)
			,
			(new TextField('MESSAGE'))
				->configureRequired()
			,
			(new ArrayField('CONTEXT'))
				->configureSerializationJson()
				->configureDefaultValue([])
			,
			(new TextField('URL')),
		];
	}
}
