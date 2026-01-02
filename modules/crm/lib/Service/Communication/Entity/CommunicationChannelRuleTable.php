<?php

namespace Bitrix\Crm\Service\Communication\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class CommunicationChannelRuleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CommunicationChannelRule_Query query()
 * @method static EO_CommunicationChannelRule_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CommunicationChannelRule_Result getById($id)
 * @method static EO_CommunicationChannelRule_Result getList(array $parameters = [])
 * @method static EO_CommunicationChannelRule_Entity getEntity()
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationChannelRule createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationChannelRule_Collection createCollection()
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationChannelRule wakeUpObject($row)
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationChannelRule_Collection wakeUpCollection($rows)
 */
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
