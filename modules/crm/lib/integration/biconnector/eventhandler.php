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

		$eventTableName = $params[3];
		if (!empty($eventTableName))
		{
			if (str_starts_with($eventTableName, 'crm_automated_solution_'))
			{
				$result = AutomatedSolutionMapping::getMapping($languageId, $eventTableName);

				return;
			}

			if (str_starts_with($eventTableName, 'crm_dynamic_items_prod_'))
			{
				$result = DynamicItemsProductMapping::getMapping($helper, $languageId, $eventTableName);

				return;
			}

			if ($eventTableName === 'crm_quote_uf')
			{
				$crmQuoteUf = QuoteUserFieldsMapping::getMapping($languageId);
				if ($crmQuoteUf)
				{
					$result['crm_quote_uf'] = $crmQuoteUf;
				}

				return;
			}

			$tableNames = [
				'crm_smart_proc',
				'crm_stages',
				'crm_entity_relation',
				'crm_quote',
				'crm_quote_product_row',
				'crm_activity_relation',
				'crm_ai_quality_assessment',
				'crm_copilot_call_assessment',
			];

			if (!in_array($eventTableName, $tableNames, true))
			{
				return;
			}

			$result[$eventTableName] = match($eventTableName)
			{
				'crm_smart_proc' => DynamicTypeMapping::getMapping(),
				'crm_stages' => StagesMapping::getMapping($helper, $languageId),
				'crm_entity_relation' => EntityRelationMapping::getMapping(),
				'crm_quote' => QuoteMapping::getMapping(),
				'crm_quote_product_row' => QuoteProductMapping::getMapping($helper),
				'crm_activity_relation' => ActivityRelationMapping::getMapping(),
				'crm_ai_quality_assessment' => AiQualityAssessmentMapping::getMapping(),
				'crm_copilot_call_assessment' => CopilotCallAssessmentMapping::getMapping(),
			};

			self::addDescriptions([
				$eventTableName,
			], $result, $languageId);

			return;
		}

		$result['crm_smart_proc'] = DynamicTypeMapping::getMapping();
		$result['crm_stages'] = StagesMapping::getMapping($helper, $languageId);
		$result['crm_entity_relation'] = EntityRelationMapping::getMapping();
		$result['crm_quote'] = QuoteMapping::getMapping();
		$result['crm_quote_product_row'] = QuoteProductMapping::getMapping($helper);
		$result['crm_activity_relation'] = ActivityRelationMapping::getMapping();
		$result['crm_ai_quality_assessment'] = AiQualityAssessmentMapping::getMapping();
		$result['crm_copilot_call_assessment'] = CopilotCallAssessmentMapping::getMapping();
		$result = array_merge(
			$result,
			AutomatedSolutionMapping::getMapping($languageId),
			DynamicItemsProductMapping::getMapping($helper, $languageId),
		);
		$crmQuoteUf = QuoteUserFieldsMapping::getMapping($languageId);
		if ($crmQuoteUf)
		{
			$result['crm_quote_uf'] = $crmQuoteUf;
		}

		self::addDescriptions([
			'crm_smart_proc',
			'crm_stages',
			'crm_entity_relation',
			'crm_quote',
			'crm_quote_product_row',
			'crm_activity_relation',
			'crm_ai_quality_assessment',
			'crm_copilot_call_assessment',
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
