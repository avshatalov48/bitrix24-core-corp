<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;

class EventHandler
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_contact to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_dynamic_type table.
	 *
	 * @param Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(Event $event): void
	{
		$params = $event->getParameters();
		$result = &$params[1];

		$result['crm_dynamic_type'] = DynamicTypeMapping::getMapping();

		self::addDescriptions(['crm_dynamic_type'], $result, $params[2]);
	}

	private static function addDescriptions(array $keys, array &$mapping, ?string $languageId): void
	{
		$messages = Loc::loadLanguageFile(__FILE__, $languageId);

		foreach ($keys as $key) {
			$entityName = strtoupper($key);
			$mapping[$key]['TABLE_DESCRIPTION'] = $messages[$entityName . '_TABLE'] ?: $key;
			foreach ($mapping[$key]['FIELDS'] as $fieldCode => &$fieldInfo) {
				$fieldInfo['FIELD_DESCRIPTION'] = $messages[$entityName . '_FIELD_' . $fieldCode];
				if (!$fieldInfo['FIELD_DESCRIPTION']) {
					$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
				}

				$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages[$entityName . '_FIELD_' . $fieldCode . '_FULL'] ?? '';
			}
			unset($fieldInfo);
		}
	}
}