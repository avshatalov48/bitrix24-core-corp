<?php

namespace Bitrix\Sign\Internal\Document;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Sign\Internal\DocumentTable;

Loc::loadMessages(__FILE__);

/**
 * Class TemplateTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Template_Query query()
 * @method static EO_Template_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Template_Result getById($id)
 * @method static EO_Template_Result getList(array $parameters = [])
 * @method static EO_Template_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Document\Template createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\Document\TemplateCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Document\Template wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\Document\TemplateCollection wakeUpCollection($rows)
 */
class TemplateTable extends Entity\DataManager
{
	public static function getObjectClass(): string
	{
		return Template::class;
	}

	public static function getCollectionClass(): string
	{
		return TemplateCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_sign_document_template';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new StringField('UID'))
				->configureRequired()
				->addValidator(new Entity\Validator\Length(32, 32))
			,
			(new StringField('TITLE'))
				->configureRequired()
				->addValidator(new Entity\Validator\Length(null, 255))
			,
			(new IntegerField('STATUS'))
				->configureRequired()
			,
			(new IntegerField('CREATED_BY_ID'))
				->configureRequired()
			,
			(new IntegerField('MODIFIED_BY_ID'))
				->configureNullable()
			,
			(new DatetimeField('DATE_CREATE'))
				->configureRequired()
			,
			(new DatetimeField('DATE_MODIFY'))
				->configureNullable()
			,
			(new IntegerField('VISIBILITY'))
				->configureRequired()
			,
			(new Entity\ReferenceField(
				'DOCUMENT',
				DocumentTable::class,
				['=this.ID' => 'ref.TEMPLATE_ID']
			)),
		];
	}
}
