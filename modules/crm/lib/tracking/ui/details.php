<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\UI;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class Details
 *
 * @package Bitrix\Crm\Tracking\UI
 */
class Details
{
	const SourceId = 'TRACKING_SOURCE_ID';

	/**
	 * Check whether is tracking field.
	 *
	 * @param $fieldName
	 * @return string
	 */
	public static function isTrackingField($fieldName)
	{
		return ($fieldName === self::SourceId);
	}

	/**
	 * Get field caption.
	 *
	 * @param $fieldName
	 * @return string
	 */
	public static function getFieldCaption($fieldName)
	{
		return ($fieldName === self::SourceId) ? Loc::getMessage('CRM_TRACKING_UI_DETAILS_FIELD_NAME') : '';
	}

	/**
	 *
	 * Check that the field value is filled
	 *
	 * @param $fieldName
	 * @param $fieldValue
	 * @return false
	 */
	public static function isTrackingFieldFilled(array $data)
	{
		$result = false;

		if (array_key_exists(self::SourceId, $data))
		{
			$sourceId = isset($data[self::SourceId]) ? $data[self::SourceId] : null;
			$actualSources = Tracking\Provider::getActualSources();
			$actualSources = array_combine(
				array_column($actualSources, 'ID'),
				array_values($actualSources)
			);
			if ($sourceId !== null && isset($actualSources[$sourceId]))
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Append entity fields.
	 *
	 * @param array &$fields Fields.
	 * @return void
	 */
	public static function appendEntityFields(array &$fields)
	{
		$fields[] = [
			'name' => self::SourceId,
			'title' => self::getFieldCaption(self::SourceId),
			//'type' => 'tracking-source',
			'type' => 'custom',
			'mergeable' => false,
			'data' => [
				'view' => self::SourceId . '_VIEW_HTML',
				'edit' => self::SourceId . '_EDIT_HTML',
			],
			'editable' => true/*,
			'enableAttributes' => false*/
		];
	}

	/**
	 * Append entity field value.
	 *
	 * @param array $entityFields
	 * @param array $data
	 */
	public static function appendEntityFieldValue(array &$entityFields, array $data)
	{
		if (array_key_exists(self::SourceId, $data))
		{
			$entityFields[self::SourceId] = $data[self::SourceId];
		}
	}

	/**
	 * Prepare entity data.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param array &$data Entity data.
	 * @param bool $isRequired
	 * @return void
	 */
	public static function prepareEntityData($entityTypeId, $entityId, array &$data, bool $isRequired = false)
	{
		ob_start();
		/** @var \CALLMain {$GLOBALS['APPLICATION']} */
		$componentResult = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:crm.tracking.entity.details',
			'view',
			[
				'SHOW_FIELD' => true,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'SOURCE_INPUT_NAME' => self::SourceId,
			]
		);
		$data[self::SourceId . '_VIEW_HTML'] = ob_get_clean();

		ob_start();
		/** @var \CALLMain {$GLOBALS['APPLICATION']} */
		$componentResult = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:crm.tracking.entity.details',
			'edit',
			[
				'SHOW_FIELD' => true,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'SOURCE_INPUT_NAME' => self::SourceId,
				'IS_REQUIRED' => $isRequired,
			]
		);
		$data[self::SourceId . '_EDIT_HTML'] = ob_get_clean();
		$data[self::SourceId] = (is_array($componentResult) && isset($componentResult['SELECTED_SOURCE_ID'])) ?
			$componentResult['SELECTED_SOURCE_ID'] : null;
	}


	/**
	 * Prepare entity data.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param array $data Entity data.
	 * @param bool $isNew Is new.
	 * @return void
	 */
	public static function saveEntityData($entityTypeId, $entityId, array $data, $isNew = false)
	{
		if (!array_key_exists(self::SourceId, $data))
		{
			return;
		}

		$sourceId = isset($data[self::SourceId]) ? $data[self::SourceId] : null;
		$actualSources = Tracking\Provider::getActualSources();
		$actualSources = array_combine(
			array_column($actualSources, 'ID'),
			array_values($actualSources)
		);
		if (!$sourceId || !isset($actualSources[$sourceId]))
		{
			$sourceId = null;
		}

		$row = Tracking\Internals\TraceEntityTable::getRowByEntity($entityTypeId, $entityId);
		if ($row && !$isNew)
		{
			$trace = Tracking\Internals\TraceTable::getRow([
				'select' => ['ID', 'SOURCE_ID'],
				'filter' => ['=ID' => $row['TRACE_ID']]
			]);
			if ($trace)
			{
				if ($trace['SOURCE_ID'] == $sourceId)
				{
					return;
				}

				Tracking\Internals\TraceTable::update($trace['ID'], ['SOURCE_ID' => $sourceId]);
				return;
			}
		}

		if (!$sourceId)
		{
			return;
		}

		$traceId = Tracking\Trace::create()->setSource($sourceId)->save();
		if (!$traceId)
		{
			return;
		}

		if ($row)
		{
			return;
		}

		Tracking\Trace::appendEntity($traceId, $entityTypeId, $entityId);
	}
}