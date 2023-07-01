<?php

namespace Bitrix\Crm\Entity;

use Bitrix\Crm\Field;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\Service\Container;

/**
 * @internal
 * @todo refactor and delete unused/no longer needed methods, logic branches, etc.
 * This class contains a lot of dirty temporary code. Please, refactor it when the new visual editor is available and
 * crm moves to bb completely.
 */
final class FieldContentType
{
	private const SUFFIX = '_CONTENT_TYPE_ID';

	private static array $fieldsCache = [];

	private function __construct()
	{
	}

	public static function compileContentTypeIdFieldName(string $regularFieldName): string
	{
		return $regularFieldName . self::SUFFIX;
	}

	public static function compileRegularFieldName(string $contentTypeIdFieldName): string
	{
		return str_replace(self::SUFFIX, '', $contentTypeIdFieldName);
	}

	public static function isContentTypeIdFieldName(string $fieldName): bool
	{
		return mb_strpos($fieldName, self::SUFFIX) !== false;
	}

	public static function compileFieldsInfo(array $regularFieldsInfo): array
	{
		$fieldsInfo = [];

		foreach ($regularFieldsInfo as $name => $field)
		{
			$isFlexibleContentType = $field['SETTINGS']['isFlexibleContentType'] ?? false;

			if ($field['TYPE'] === Field::TYPE_TEXT && $isFlexibleContentType === true)
			{
				$fieldsInfo[self::compileContentTypeIdFieldName($name)] = [
					'TYPE' => Field::TYPE_INTEGER,
					'ATTRIBUTES' => [
						\CCrmFieldInfoAttr::HasDefaultValue,
						\CCrmFieldInfoAttr::CanNotBeEmptied,
						\CCrmFieldInfoAttr::NotDisplayed,
						\CCrmFieldInfoAttr::Hidden,
					],
					'CLASS' => Field\ContentTypeId::class,
				];
			}
		}

		return $fieldsInfo;
	}

	public static function getFieldsWithFlexibleContentType(int $entityTypeId): array
	{
		if (isset(self::$fieldsCache[$entityTypeId]))
		{
			return self::$fieldsCache[$entityTypeId];
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return [];
		}

		$fields = [];
		foreach ($factory->getFieldsCollection() as $field)
		{
			$isFlexibleContentType = $field->getSettings()['isFlexibleContentType'] ?? false;
			if ($isFlexibleContentType === true && $field->getType() === Field::TYPE_TEXT)
			{
				$fields[] = $field->getName();
			}
		}

		self::$fieldsCache[$entityTypeId] = array_unique($fields);

		return self::$fieldsCache[$entityTypeId];
	}

	public static function enrichGridRow(
		int $entityTypeId,
		array $fieldToContentTypeMap,
		array $rawData,
		array $row
	): array
	{
		foreach (self::getFieldsWithFlexibleContentType($entityTypeId) as $singleField)
		{
			$contentTypeId = $fieldToContentTypeMap[$singleField] ?? \CCrmContentType::Undefined;

			$rawValue = $rawData['~' . $singleField] ?? null;
			if (!is_string($rawValue))
			{
				$rawValue = '';
			}

			if ($contentTypeId === \CCrmContentType::BBCode)
			{
				$row[$singleField] = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($rawValue));
			}
			else
			{
				$row[$singleField] = TextHelper::sanitizeHtml($rawValue);
			}
		}

		return $row;
	}

	public static function enrichRecycleBinFields(ItemIdentifier $itemToDelete, array $fields): array
	{
		foreach (FieldContentTypeTable::loadForItem($itemToDelete) as $fieldName => $contentTypeId)
		{
			$fields[self::compileContentTypeIdFieldName($fieldName)] = $contentTypeId;
		}

		return $fields;
	}

	//region Entity Editor
	public static function compileFieldDescriptionForDetails(int $entityTypeId, int $entityId, string $field): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$contentTypeId = \CCrmContentType::Html;
		if ($entityId > 0)
		{
			$contentTypeId = FieldContentTypeTable::getContentTypeId(new ItemIdentifier($entityTypeId, $entityId), $field);
		}

		return [
			'name' => $field,
			'title' => $factory ? $factory->getFieldCaption($field) : $field,
			'type' => $contentTypeId === \CCrmContentType::BBCode ? 'bb' : 'html',
			'editable' => true,
		];
	}

	public static function prepareFieldsFromDetailsToView(int $entityTypeId, int $entityId, array $fields): array
	{
		$contentTypes = [];
		if ($entityId > 0)
		{
			$contentTypes = FieldContentTypeTable::loadForItem(new ItemIdentifier($entityTypeId, $entityId));
		}

		foreach (self::getFieldsWithFlexibleContentType($entityTypeId) as $fieldName)
		{
			if (empty($fields[$fieldName]))
			{
				continue;
			}

			$contentTypeId = $contentTypes[$fieldName] ?? \CCrmContentType::Undefined;

			if ($contentTypeId === \CCrmContentType::BBCode)
			{
				$fields[$fieldName . '_HTML'] = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($fields[$fieldName]));
			}
			else
			{
				$fields[$fieldName] = TextHelper::sanitizeHtml($fields[$fieldName]);
			}
		}

		return $fields;
	}

	public static function prepareFieldsFromDetailsToSave(int $entityTypeId, int $entityId, array $fields): array
	{
		$contentTypes = [];
		if ($entityId > 0)
		{
			$contentTypes = FieldContentTypeTable::loadForItem(new ItemIdentifier($entityTypeId, $entityId));
		}

		foreach (self::getFieldsWithFlexibleContentType($entityTypeId) as $fieldName)
		{
			// content type can't be changed from interface
			unset($fields[self::compileContentTypeIdFieldName($fieldName)]);

			$contentTypeId = $contentTypes[$fieldName] ?? \CCrmContentType::Undefined;

			$isLegitimateBBContent = $entityId > 0 && $contentTypeId === \CCrmContentType::BBCode;
			if (!$isLegitimateBBContent)
			{
				$fields[self::compileContentTypeIdFieldName($fieldName)] = \CCrmContentType::Html;
				if (!empty($fields[$fieldName]))
				{
					$fields[$fieldName] = TextHelper::sanitizeHtml($fields[$fieldName]);
				}
			}
		}

		return $fields;
	}

	public static function prepareSaveOptionsForDetails(int $entityTypeId, int $entityId): array
	{
		return [
			'PRESERVE_CONTENT_TYPE' => self::shouldPreserveContentTypeInDetails($entityTypeId, $entityId),
		];
	}

	public static function shouldPreserveContentTypeInDetails(int $entityTypeId, int $entityId): bool
	{
		if ($entityId <= 0)
		{
			return false;
		}

		// preserve if at least one field is bb
		return in_array(
			\CCrmContentType::BBCode,
			FieldContentTypeTable::loadForItem(new ItemIdentifier($entityTypeId, $entityId)),
			true,
		);
	}
	//endregion

	//region Old Rest
	public static function prepareFieldsFromCompatibleRestToSave(int $entityTypeId, array $fields): array
	{
		foreach (self::getFieldsWithFlexibleContentType($entityTypeId) as $fieldName)
		{
			// compatible rest can manipulate only html values
			$fields[self::compileContentTypeIdFieldName($fieldName)] = \CCrmContentType::Html;
			if (isset($fields[$fieldName]))
			{
				$fields[$fieldName] = TextHelper::sanitizeHtml($fields[$fieldName]);
			}
		}

		return $fields;
	}

	public static function prepareFieldsFromCompatibleRestToRead(int $entityTypeId, int $entityId, array $fields): array
	{
		$contentTypes = FieldContentTypeTable::loadForItem(new ItemIdentifier($entityTypeId, $entityId));

		foreach (self::getFieldsWithFlexibleContentType($entityTypeId) as $fieldName)
		{
			if (empty($fields[$fieldName]))
			{
				continue;
			}

			$contentTypeId = $contentTypes[$fieldName] ?? \CCrmContentType::Undefined;

			if ($contentTypeId === \CCrmContentType::BBCode)
			{
				$fields[$fieldName] = TextHelper::convertBbCodeToHtml($fields[$fieldName]);
			}
		}

		return $fields;
	}
	//endregion

	public static function prepareFieldsFromRestToRead(ItemIdentifier $item, array $fields): array
	{
		$contentTypes = FieldContentTypeTable::loadForItem($item);

		foreach (self::getFieldsWithFlexibleContentType($item->getEntityTypeId()) as $fieldName)
		{
			$contentTypeId = $contentTypes[$fieldName] ?? \CCrmContentType::Undefined;
			if (isset($fields[$fieldName]) && $contentTypeId === \CCrmContentType::BBCode)
			{
				$fields[$fieldName] = TextHelper::convertBbCodeToHtml($fields[$fieldName]);
			}
		}

		return $fields;
	}
}
