<?php

namespace Bitrix\Crm\Integration\BiConnector;

use Bitrix\BIConnector\DB\MysqliConnection;
use Bitrix\Main\Event;

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
		$languageId = $params[2];

		/** @var MysqliConnection $connection */
		$connection = $params[0]->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		$result['crm_smart_proc'] = DynamicTypeMapping::getMapping();
		$result['crm_stages'] = StagesMapping::getMapping($helper, $languageId);
		$result['crm_entity_relation'] = EntityRelationMapping::getMapping();
		$result['crm_quote'] = QuoteMapping::getMapping();
		$result['crm_quote_product_row'] = QuoteProductMapping::getMapping($helper);
		$result['crm_activity_relation'] = ActivityRelationMapping::getMapping();
		$result['crm_ai_quality_assessment'] = AiQualityAssessmentMapping::getMapping();
		$result = array_merge(
			$result,
			AutomatedSolutionMapping::getMapping($languageId),
			DynamicItemsProductMapping::getMapping($helper, $languageId),
		);

		self::addDescriptions([
			'crm_smart_proc',
			'crm_stages',
			'crm_entity_relation',
			'crm_quote',
			'crm_quote_product_row',
			'crm_activity_relation',
			'crm_ai_quality_assessment',
		], $result, $languageId);
	}

	private static function addDescriptions(array $keys, array &$mapping, ?string $languageId): void
	{
		foreach ($keys as $key)
		{
			$entityName = strtoupper($key);
			$mapping[$key]['TABLE_DESCRIPTION'] = Localization::getMessage($entityName . '_TABLE', $languageId) ?: $key;
			foreach ($mapping[$key]['FIELDS'] as $fieldCode => &$fieldInfo)
			{
				$fieldInfo['FIELD_DESCRIPTION'] =  Localization::getMessage($entityName . '_FIELD_' . $fieldCode, $languageId) ?: $fieldCode;
				$fieldInfo['FIELD_DESCRIPTION_FULL'] = Localization::getMessage($entityName . '_FIELD_' . $fieldCode . '_FULL', $languageId) ?? '';
			}
			unset($fieldInfo);
		}
	}
}
