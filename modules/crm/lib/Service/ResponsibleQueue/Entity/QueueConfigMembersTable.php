<?php

namespace Bitrix\Crm\Service\ResponsibleQueue\Entity;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class QueueConfigMembersTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_QueueConfigMembers_Query query()
 * @method static EO_QueueConfigMembers_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_QueueConfigMembers_Result getById($id)
 * @method static EO_QueueConfigMembers_Result getList(array $parameters = [])
 * @method static EO_QueueConfigMembers_Entity getEntity()
 * @method static \Bitrix\Crm\Service\ResponsibleQueue\Entity\EO_QueueConfigMembers createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Service\ResponsibleQueue\Entity\EO_QueueConfigMembers_Collection createCollection()
 * @method static \Bitrix\Crm\Service\ResponsibleQueue\Entity\EO_QueueConfigMembers wakeUpObject($row)
 * @method static \Bitrix\Crm\Service\ResponsibleQueue\Entity\EO_QueueConfigMembers_Collection wakeUpCollection($rows)
 */
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
