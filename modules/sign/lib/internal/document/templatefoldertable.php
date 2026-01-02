<?php

namespace Bitrix\Sign\Internal\Document;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class TemplateFolderTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateFolder_Query query()
 * @method static EO_TemplateFolder_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TemplateFolder_Result getById($id)
 * @method static EO_TemplateFolder_Result getList(array $parameters = [])
 * @method static EO_TemplateFolder_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Document\TemplateFolder createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\Document\TemplateFolderCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Document\TemplateFolder wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\Document\TemplateFolderCollection wakeUpCollection($rows)
 */
class TemplateFolderTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return TemplateFolder::class;
	}

	public static function getCollectionClass(): string
	{
		return TemplateFolderCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_sign_document_template_folder';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new StringField('TITLE'))
				->configureRequired()
				->addValidator(new Entity\Validator\Length(null, 255))
			,
			(new IntegerField('CREATED_BY_ID'))
				->configureRequired()
			,
			(new DatetimeField('DATE_CREATE'))
				->configureRequired()
			,
			(new IntegerField('VISIBILITY'))
				->configureRequired()
			,
			(new IntegerField('STATUS'))
				->configureRequired()
			,
			(new IntegerField('MODIFIED_BY_ID'))
				->configureNullable()
			,
			(new DatetimeField('DATE_MODIFY'))
				->configureNullable()
			,
			(new Entity\ReferenceField(
				'TEMPLATE',
				TemplateTable::class,
				['=this.ID' => 'ref.FOLDER_ID']
			)),
		];
	}
}