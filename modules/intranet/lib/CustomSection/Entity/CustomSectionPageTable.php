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
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\RegExpValidator;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class CustomSectionPageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CustomSectionPage_Query query()
 * @method static EO_CustomSectionPage_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CustomSectionPage_Result getById($id)
 * @method static EO_CustomSectionPage_Result getList(array $parameters = array())
 * @method static EO_CustomSectionPage_Entity getEntity()
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection createCollection()
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage wakeUpObject($row)
 * @method static \Bitrix\Intranet\CustomSection\Entity\EO_CustomSectionPage_Collection wakeUpCollection($rows)
 */
class CustomSectionPageTable extends DataManager
{
	/**
	 * @inheritDoc
	 */
	public static function getTableName(): string
	{
		return 'b_intranet_custom_section_page';
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
			(new IntegerField('CUSTOM_SECTION_ID'))
				->configureRequired()
			,
			(new Reference(
				'CUSTOM_SECTION',
				CustomSectionTable::class,
				Join::on('this.CUSTOM_SECTION_ID', 'ref.ID')
			)),
			(new StringField('CODE'))
				->configureSize(255)
				->configureRequired()
				->configureUnique()
				->addValidator(new RegExpValidator(Manager::VALID_CODE_REGEX))
				->configureTitle(Loc::getMessage('INTRANET_CUSTOM_SECTION_PAGE_TABLE_FIELD_TITLE_CODE'))
			,
			(new StringField('TITLE'))
				->configureSize(255)
				->configureRequired()
				->configureTitle(Loc::getMessage('INTRANET_CUSTOM_SECTION_PAGE_TABLE_FIELD_TITLE_TITLE'))
			,
			(new IntegerField('SORT'))
				->configureRequired()
			,
			(new StringField('MODULE_ID'))
				->configureSize(50)
				->configureRequired()
			,
			(new StringField('SETTINGS'))
				->configureSize(255)
				->configureRequired()
				->configureDefaultValue('')
				->addValidator(
					new LengthValidator(
						null,
						255
					)
				)
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

		$id = $event->getParameter('primary')['ID'];
		$customSectionId =
			self::extractFieldFromOrmEvent($event, $result, 'CUSTOM_SECTION_ID')
			?: static::getCustomSectionId($id)
		;

		$codeGenerator = static::getCodeGenerator($customSectionId);
		if (!$codeGenerator->isCodeValid((string)$code))
		{
			$newCode = $codeGenerator->generate(self::extractFieldFromOrmEvent($event, $result, 'TITLE'));

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

	/**
	 * Returns a CodeGenerator for the Page with generation relative to the code of other pages in the customSection.
	 * If customSectionId === null, then generation will be relative to all pages
	 *
	 * @param int|null $customSectionId
	 * @return CodeGenerator
	 */
	protected static function getCodeGenerator(?int $customSectionId = null): CodeGenerator
	{
		/** @var StringField $codeField */
		$codeField = static::getEntity()->getField('CODE');
		$generator = new CodeGenerator(static::class, $codeField);

		if (is_null($customSectionId))
		{
			return $generator;
		}

		$customSectionCode = static::getCustomSectionCode($customSectionId);
		if (is_null($customSectionCode))
		{
			return $generator;
		}

		$manager = ServiceLocator::getInstance()->get('intranet.customSection.manager');
		$systemPagesCodes = $manager->getSystemPagesCodes($customSectionCode, true);

		return $generator
			->setUniqueCheckFilter(['=CUSTOM_SECTION_ID' => $customSectionId])
			->setUniqueCheckCallback(static function ($code) use ($systemPagesCodes) {
				return !in_array($code, $systemPagesCodes, true);
			})
		;
	}

	protected static function getCustomSectionId(int $customSectionPageId): ?int
	{
		$result = static::query()
			->setSelect(['CUSTOM_SECTION_ID'])
			->where('ID', $customSectionPageId)
			->exec()
			->fetch()
		;

		return isset($result['CUSTOM_SECTION_ID'])
			? (int)$result['CUSTOM_SECTION_ID']
			: null
		;
	}

	protected static function getCustomSectionCode(int $customSectionId): ?string
	{
		$result = CustomSectionTable::query()
			->setSelect(['CODE'])
			->where('ID', $customSectionId)
			->exec()
			->fetch()
		;

		return $result['CODE'] ?? null;
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
