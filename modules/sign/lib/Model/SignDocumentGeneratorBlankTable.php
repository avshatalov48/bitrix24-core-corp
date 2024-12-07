<?php

namespace Bitrix\Sign\Model;

use Bitrix\Main\ORM;

/**
 * Class SignDocumentGeneratorBlankTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SignDocumentGeneratorBlank_Query query()
 * @method static EO_SignDocumentGeneratorBlank_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SignDocumentGeneratorBlank_Result getById($id)
 * @method static EO_SignDocumentGeneratorBlank_Result getList(array $parameters = [])
 * @method static EO_SignDocumentGeneratorBlank_Entity getEntity()
 * @method static \Bitrix\Sign\Model\SignDocumentGeneratorBlank createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection createCollection()
 * @method static \Bitrix\Sign\Model\SignDocumentGeneratorBlank wakeUpObject($row)
 * @method static \Bitrix\Sign\Model\EO_SignDocumentGeneratorBlank_Collection wakeUpCollection($rows)
 */
class SignDocumentGeneratorBlankTable extends ORM\Data\DataManager
{
	public static function getObjectClass()
	{
		return SignDocumentGeneratorBlank::class;
	}

	public static function getTableName(): string
	{
		return 'b_sign_documentgenerator_blank';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID'),
			(new ORM\Fields\IntegerField('BLANK_ID'))
				->configureTitle('Blank ID')
				->configureRequired()
			,
			(new ORM\Fields\IntegerField('DOCUMENT_GENERATOR_TEMPLATE_ID'))
				->configureTitle('Template ID')
				->configureRequired()
			,
			(new ORM\Fields\StringField('INITIATOR'))
				->configureTitle('Your side')
				->configureSize(1024)
				->configureDefaultValue("")
			,
			(new ORM\Fields\DatetimeField('CREATED_AT'))
				->configureTitle('Created on')
			,
		];
	}

	public static function linkToSignBlank(DocumentGeneratorBlankTemplate $generatorTemplate)
	{
		$exists = self::getList([
			'select' => ['ID', 'BLANK_ID',],
			'filter' =>
				[
					'=DOCUMENT_GENERATOR_TEMPLATE_ID' => $generatorTemplate->getTemplateId(),
				],
			'limit' => 1,
		])->fetch();

		if ($generatorTemplate->getEnabled() !== 'Y')
		{
			if ($exists)
			{
				self::delete($exists['ID']);
			}
			return;
		}

		if ($exists && $exists['BLANK_ID'] === $generatorTemplate->getBlankId())
		{
			return;
		}

		if ($exists && $exists['BLANK_ID'] !== $generatorTemplate->getBlankId())
		{
			self::delete($exists['ID']);
		}

		self::add([
			'BLANK_ID' => $generatorTemplate->getBlankId(),
			'DOCUMENT_GENERATOR_TEMPLATE_ID' => $generatorTemplate->getTemplateId(),
			'INITIATOR' => $generatorTemplate->getYourSide(),
		]);
	}
}