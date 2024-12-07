<?php

namespace Bitrix\Crm\Entity;

use Bitrix\Crm\Field;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\FieldContentTypeTable;
use Bitrix\Crm\Service\Container;

/**
 * @internal
 */
final class CommentsHelper
{
	private static array $fieldsCache = [];

	private function __construct()
	{
	}

	public static function normalizeComment($commentContent, array $extraWhiteList = []): string
	{
		// current html editor sends ' symbol as html entity to backend
		// convert it back for consistency with other html special symbols
		$commentContent = (string)str_replace('&#39;', "'", (string)$commentContent);

		$result = TextHelper::sanitizeBbCode(
			TextHelper::convertHtmlToBbCode($commentContent),
			['user', 'disk file id'],
			$extraWhiteList,
		);

		// special handling of [ and ] html entities
		// as in bb code those symbols are special, there is no way to add them to a text literally except for those entities
		// so protect them from double encoding
		return (string)str_replace(
			['&amp;#91;', '&amp;#93;'],
			['&#91;', '&#93;'],
			$result
		);
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
			if ($field->getType() === Field::TYPE_TEXT && $field->getValueType() === Field::VALUE_TYPE_BB)
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
				$hasParagraph = preg_match('/\[p]/i', $rawValue) === 1;
				$useTypography = $hasParagraph;

				$content = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($rawValue, $useTypography));
				if ($useTypography)
				{
					$content = "<div class='crm-bbcode-container'>{$content}</div>";
				}

				$row[$singleField] = $content;
			}
			else
			{
				$row[$singleField] = TextHelper::sanitizeHtml($rawValue);
			}
		}

		return $row;
	}

	//region Entity Editor
	public static function compileFieldDescriptionForDetails(int $entityTypeId, string $field, int $entityId): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		$copilotOptions = [];
		$isCopilotEnabled = AIManager::isEnabledInGlobalSettings(EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE);
		if ($isCopilotEnabled)
		{
			$entityName = $factory ? strtolower($factory->getEntityName()) : '';
			$fieldId = strtolower($field);
			$contextId = "crm_editor-{$entityName}-{$entityId}-{$fieldId}";
			$copilotOptions = [
				'copilotOptions' => [
					'moduleId' => 'crm',
					'contextId' => $contextId,
					'category' => 'crm_comment_field',
					'autoHide' => true,
				],
				'triggerBySpace' => true,
			];
		}

		return [
			'name' => $field,
			'title' => $factory ? $factory->getFieldCaption($field) : $field,
			'type' => 'bbcode',
			'editable' => true,
			'data' => [
				'editorOptions' => [
					'copilot' => $copilotOptions,
					'paragraphPlaceholder' => 'auto',
				],
			],
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

			$contentTypeId = $contentTypes[$fieldName] ?? FieldContentTypeTable::getContentTypeIdForAbsentEntry();

			if ($contentTypeId === \CCrmContentType::BBCode)
			{
				$bb = htmlspecialcharsback($fields[$fieldName]);
				$html = TextHelper::convertBbCodeToHtml($fields[$fieldName]);
			}
			else
			{
				$bb = TextHelper::convertHtmlToBbCode($fields[$fieldName]);
				$html = $fields[$fieldName];
			}

			$fields[$fieldName] = $bb;
			$fields[$fieldName . '_HTML'] = TextHelper::sanitizeHtml($html);
		}

		return $fields;
	}

	public static function prepareFieldsFromEditorAdapterToView(int $entityTypeId, array $fields): array
	{
		foreach (self::getFieldsWithFlexibleContentType($entityTypeId) as $fieldName)
		{
			if (empty($fields[$fieldName]))
			{
				continue;
			}

			$fields[$fieldName] = htmlspecialcharsback($fields[$fieldName]);

			/*
			 * EditorAdapter fetches comments from Item, and Item always returns bb code
			 */
			$fields[$fieldName . '_HTML'] = TextHelper::sanitizeHtml(TextHelper::convertBbCodeToHtml($fields[$fieldName]));
		}

		return $fields;
	}
	//endregion


	public static function prepareFieldsFromCompatibleRestToReadInList(int $entityTypeId, array $entityMap): array
	{
		$entityIds = array_keys($entityMap);
		if (empty($entityIds))
		{
			return $entityMap;
		}

		$contentTypesMap = FieldContentTypeTable::loadForMultipleItems($entityTypeId, $entityIds);
		foreach ($entityMap as $entityId => &$fields)
		{
			$fields = self::prepareFieldsFromCompatibleRestToRead(
				$entityTypeId,
				$entityId,
				$fields,
				$contentTypesMap[$entityId] ?? [],
			);
		}

		return $entityMap;
	}

	public static function prepareFieldsFromCompatibleRestToRead(
		int $entityTypeId,
		int $entityId,
		array $fields,
		?array $preloadedContentTypes = null
	): array
	{
		$flexibleFields = self::getFieldsWithFlexibleContentType($entityTypeId);
		if (empty($flexibleFields))
		{
			return $fields;
		}

		$isFlexibleFieldsInFields = array_intersect_key($fields, array_flip($flexibleFields));
		if (!$isFlexibleFieldsInFields)
		{
			return $fields;
		}

		$contentTypes = $preloadedContentTypes ?? FieldContentTypeTable::loadForItem(new ItemIdentifier($entityTypeId, $entityId));

		foreach ($flexibleFields as $fieldName)
		{
			if (empty($fields[$fieldName]))
			{
				continue;
			}

			$contentTypeId = $contentTypes[$fieldName] ?? FieldContentTypeTable::getContentTypeIdForAbsentEntry();

			if ($contentTypeId !== \CCrmContentType::BBCode)
			{
				$fields[$fieldName] = TextHelper::convertHtmlToBbCode($fields[$fieldName]);
			}
		}

		return $fields;
	}

	public static function prepareFieldsFromBizProc(int $entityTypeId, int $entityId, array $fields): array
	{
		if (!\CCrmOwnerType::IsDefined($entityTypeId) || $entityId <= 0)
		{
			return $fields;
		}

		$fieldNamesToConvert = self::getFieldsWithFlexibleContentType($entityTypeId);
		if (empty($fieldNamesToConvert))
		{
			return $fields;
		}

		$contentTypes = FieldContentTypeTable::loadForItem(new ItemIdentifier($entityTypeId, $entityId));

		foreach ($fieldNamesToConvert as $fieldName)
		{
			$contentTypeId = $contentTypes[$fieldName] ?? FieldContentTypeTable::getContentTypeIdForAbsentEntry();

			if (!empty($fields[$fieldName]) && is_string($fields[$fieldName]) && $contentTypeId !== \CCrmContentType::BBCode)
			{
				$fields[$fieldName] = self::normalizeComment($fields[$fieldName]);
			}
		}

		return $fields;
	}
}
