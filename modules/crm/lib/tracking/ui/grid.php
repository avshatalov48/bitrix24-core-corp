<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\UI;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Grid
 *
 * @package Bitrix\Crm\Tracking\UI
 */
class Grid
{
	const COLUMN_TRACKING_PATH = 'TRACKING_PATH';
	/**
	 * Append grid columns.
	 *
	 * @param array &$columns Columns.
	 * @return void
	 */
	public static function appendColumns(array &$columns)
	{
		$columns[] = [
			'id' => self::COLUMN_TRACKING_PATH,
			'name' => Loc::getMessage("CRM_TRACKING_UI_GRID_PATH"),
			'sort' => false,
			'default' => true,
			'editable' => false
		];
	}

	/**
	 * Enrich source name.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param string $sourceName Source name.
	 * @return string
	 */
	public static function enrichSourceName($entityTypeId, $entityId, $sourceName)
	{
		ob_start();
		/** @var \CALLMain {$GLOBALS['APPLICATION']} */
		$GLOBALS['APPLICATION']->includeComponent(
			'bitrix:crm.tracking.entity.path',
			'',
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'ONLY_SOURCE_ICON' => true
			],
			null,
			["HIDE_ICONS"=>"Y"]
		);

		$adsSource = ob_get_clean();
		if (!$adsSource)
		{
			return $sourceName;
		}

		return ($sourceName ? $sourceName . ', ' : '') . $adsSource;
	}

	/**
	 * Append grid rows.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param array &$rows Rows.
	 * @return void
	 */
	public static function appendRows($entityTypeId, $entityId, array &$rows)
	{
		ob_start();
		/** @var \CALLMain {$GLOBALS['APPLICATION']} */
		$GLOBALS['APPLICATION']->includeComponent(
			'bitrix:crm.tracking.entity.path',
			'',
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
			],
			null,
			["HIDE_ICONS"=>"Y"]
		);

		$rows[self::COLUMN_TRACKING_PATH] = ob_get_clean();
	}
}