<?php

namespace Bitrix\Intranet\CustomSection\Entity;

use Bitrix\Intranet\CustomSection\Manager;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\RegExpValidator;

/**
 * Class CustomSectionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CustomSection_Query query()
 * @method static EO_CustomSection_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CustomSection_Result getById($id)
 * @method static EO_CustomSection_Result getList(array $parameters = array())
 * @method static EO_CustomSection_Entity getEntity()
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection createCollection()
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection wakeUpObject($row)
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSection_Collection wakeUpCollection($rows)
 */
class CustomSectionTable extends DataManager
{
	/**
	 * @inheritDoc
	 */
	public static function getTableName(): string
	{
		return 'b_intranet_custom_section';
	}

	/**
	 * @inheritDoc
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new StringField('CODE'))
				->configureSize(255)
				->configureRequired()
				->configureUnique()
				->addValidator(new RegExpValidator(Manager::VALID_CODE_REGEX))
				->configureTitle(Loc::getMessage('INTRANET_CUSTOM_SECTION_TABLE_FIELD_TITLE_CODE'))
			,
			(new StringField('TITLE'))
				->configureSize(255)
				->configureRequired()
				->configureTitle(Loc::getMessage('INTRANET_CUSTOM_SECTION_TABLE_FIELD_TITLE_TITLE'))
			,
			(new StringField('MODULE_ID'))
				->configureSize(50)
				->configureRequired()
			,
			(new OneToMany('PAGES', CustomSectionPageTable::class, 'CUSTOM_SECTION'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,
		];
	}

	public static function onBeforeAdd(Event $event): EventResult
	{
		$result = new EventResult();
		self::trimTitle($event, $result);
		self::fillCodeIfEmptyOrInvalid($event, $result, false);

		return $result;
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		$result = new EventResult();
		self::trimTitle($event, $result);
		self::fillCodeIfEmptyOrInvalid($event, $result, true);

		return $result;
	}

	private static function trimTitle(Event $event, EventResult $result): void
	{
		$title = self::extractFieldFromOrmEvent($event, $result, 'TITLE');
		if (!is_string($title))
		{
			return;
		}

		$trimmedTitle = trim($title);
		if ($trimmedTitle !== $title)
		{
			$result->modifyFields(
				$result->getModified() + ['TITLE' => $trimmedTitle],
			);
		}
	}

	protected static function fillCodeIfEmptyOrInvalid(Event $event, EventResult $result, bool $isUpdate): void
	{
		$code = self::extractFieldFromOrmEvent($event, $result, 'CODE');
		if ($isUpdate && is_null($code))
		{
			return;
		}

		if (!static::getCodeGenerator()->isCodeValid((string)$code))
		{
			$newCode = static::getCodeGenerator()->generate(self::extractFieldFromOrmEvent($event, $result, 'TITLE'));

			if (empty($newCode))
			{
				$result->addError(new EntityError('CODE value is invalid. Could not generate new one automatically'));
			}
			else
			{
				$result->modifyFields(
					$result->getModified() + ['CODE' => $newCode],
				);
			}
		}
	}

	private static function extractFieldFromOrmEvent(Event $event, EventResult $result, string $fieldName): mixed
	{
		$fields = $event->getParameter('fields') ?? [];

		return $result->getModified()[$fieldName] ?? $fields[$fieldName] ?? null;
	}

	protected static function getCodeGenerator(): CodeGenerator
	{
		static $generator = null;

		if (is_null($generator))
		{
			/** @var StringField $codeField */
			$codeField = static::getEntity()->getField('CODE');

			$generator = (new CodeGenerator(static::class, $codeField));
		}

		return $generator;
	}

	public static function onAfterAdd(Event $event): void
	{
		static::clearCache();
	}

	public static function onAfterUpdate(Event $event): void
	{
		static::clearCache();
	}

	public static function onAfterDelete(Event $event): void
	{
		static::clearCache();
	}

	protected static function clearCache(): void
	{
		ServiceLocator::getInstance()->get('intranet.customSection.manager')->clearLeftMenuCache();
	}
}
