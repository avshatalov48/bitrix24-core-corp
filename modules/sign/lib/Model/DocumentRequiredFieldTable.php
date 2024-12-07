<?php

namespace Bitrix\Sign\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class DocumentRequiredFieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DocumentRequiredField_Query query()
 * @method static EO_DocumentRequiredField_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DocumentRequiredField_Result getById($id)
 * @method static EO_DocumentRequiredField_Result getList(array $parameters = [])
 * @method static EO_DocumentRequiredField_Entity getEntity()
 * @method static \Bitrix\Sign\Model\EO_DocumentRequiredField createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection createCollection()
 * @method static \Bitrix\Sign\Model\EO_DocumentRequiredField wakeUpObject($row)
 * @method static \Bitrix\Sign\Model\EO_DocumentRequiredField_Collection wakeUpCollection($rows)
 */
class DocumentRequiredFieldTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_sign_document_required_field';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID'),
			(new IntegerField('DOCUMENT_ID'))
				->configureTitle('Document ID')
				->configureRequired()
			,
			(new StringField('TYPE'))
				->configureTitle('Field type')
				->configureRequired()
			,
			(new IntegerField('ROLE'))
				->configureTitle('Role')
				->configureRequired()
			,
		];
	}
}