<?php

namespace Bitrix\Crm\Service\Logger\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ArrayField;

/**
 * Class LogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Log_Query query()
 * @method static EO_Log_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Log_Result getById($id)
 * @method static EO_Log_Result getList(array $parameters = [])
 * @method static EO_Log_Entity getEntity()
 * @method static \Bitrix\Crm\Service\Logger\Model\EO_Log createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Service\Logger\Model\EO_Log_Collection createCollection()
 * @method static \Bitrix\Crm\Service\Logger\Model\EO_Log wakeUpObject($row)
 * @method static \Bitrix\Crm\Service\Logger\Model\EO_Log_Collection wakeUpCollection($rows)
 */
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
