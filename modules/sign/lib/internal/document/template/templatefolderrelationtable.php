<?php

namespace Bitrix\Sign\Internal\Document\Template;


use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Sign\Internal\Document\TemplateFolderTable;
use Bitrix\Sign\Internal\Document\TemplateTable;
use Bitrix\Sign\Trait\ORM\UpdateByFilterTrait;
use Bitrix\Sign\Type\Template\EntityType;

/**
 * Class TemplateFolderRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TemplateFolderRelation_Query query()
 * @method static EO_TemplateFolderRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TemplateFolderRelation_Result getById($id)
 * @method static EO_TemplateFolderRelation_Result getList(array $parameters = [])
 * @method static EO_TemplateFolderRelation_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Document\Template\TemplateFolderRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\Document\Template\TemplateFolderRelationCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Document\Template\TemplateFolderRelation wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\Document\Template\TemplateFolderRelationCollection wakeUpCollection($rows)
 */
class TemplateFolderRelationTable extends Entity\DataManager
{
	use DeleteByFilterTrait;
	use UpdateByFilterTrait;

	public static function getObjectClass(): string
	{
		return TemplateFolderRelation::class;
	}

	public static function getTableName(): string
	{
		return 'b_sign_document_template_folder_relation';
	}

	public static function getCollectionClass(): string
	{
		return TemplateFolderRelationCollection::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('ENTITY_ID'))
				->configureTitle('Entity ID')
				->configureRequired()
			,
			(new IntegerField('PARENT_ID'))
				->configureTitle('Parent ID')
				->configureDefaultValue(0)
			,
			(new StringField('ENTITY_TYPE'))
				->configureTitle('Entity type')
				->addValidator(new LengthValidator(1, 255))
				->configureRequired(),
			(new IntegerField('DEPTH_LEVEL'))
				->configureTitle('Depth level')
				->configureRequired()
			,
			(new IntegerField('CREATED_BY_ID'))
				->configureTitle('Created by id')
				->configureRequired()
			,
			(new ReferenceField(
				'FOLDER',
				TemplateFolderTable::class,
				Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', EntityType::FOLDER->value)
			))
			,
			(new ReferenceField(
				'TEMPLATE',
				TemplateTable::class,
				Join::on('this.ENTITY_ID', 'ref.ID')->where('this.ENTITY_TYPE', EntityType::TEMPLATE->value)
			))
			,
		];
	}
}