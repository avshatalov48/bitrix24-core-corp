<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Text\Emoji;

class CustomFieldTable extends DataManager
{
	public const FIELD_UID_MIN_LENGTH = 10;
	public const FIELD_UID_MAX_LENGTH = 255;

	public static function getTableName(): string
	{
		return 'b_crm_documentgenerator_template_custom_field';
	}

	public static function getObjectClass(): string
	{
		return CustomField::class;
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			(new IntegerField('TEMPLATE_ID'))
				->configureRequired()
			,
			(new StringField('FIELD_UID'))
				->configureRequired()
				->configureSize(255)
				->addValidator(new LengthValidator(self::FIELD_UID_MIN_LENGTH, self::FIELD_UID_MAX_LENGTH))
			,
			(new StringField('FIELD_VALUE'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode'])
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
		];
	}

	final public static function deleteByTemplateId(int $templateId): void
	{
		if ($templateId <= 0)
		{
			return;
		}

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(
			sprintf(
				'DELETE FROM %s WHERE TEMPLATE_ID = %d',
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($templateId)
			)
		);

		self::cleanCache();
	}
}
