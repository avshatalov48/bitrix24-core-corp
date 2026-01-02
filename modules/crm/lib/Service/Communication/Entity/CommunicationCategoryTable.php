<?php

namespace Bitrix\Crm\Service\Communication\Entity;

use Bitrix\Main\DI\ServiceLocator;

/**
 * Class CommunicationCategoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CommunicationCategory_Query query()
 * @method static EO_CommunicationCategory_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CommunicationCategory_Result getById($id)
 * @method static EO_CommunicationCategory_Result getList(array $parameters = [])
 * @method static EO_CommunicationCategory_Entity getEntity()
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationCategory createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationCategory_Collection createCollection()
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationCategory wakeUpObject($row)
 * @method static \Bitrix\Crm\Service\Communication\Entity\EO_CommunicationCategory_Collection wakeUpCollection($rows)
 */
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
