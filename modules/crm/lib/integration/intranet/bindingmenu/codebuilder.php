<?php

namespace Bitrix\Crm\Integration\Intranet\BindingMenu;

use Bitrix\Main\ArgumentException;

abstract class CodeBuilder
{
	/**
	 * Use this method to generate a value for a 'MENU_CODE' param of a 'intranet.binding.menu' component.
	 *
	 * @param int $entityTypeId
	 *
	 * @return string
	 */
	public static function getMenuCode(int $entityTypeId): string
	{
		return mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
	}

	public static function getMapItemCode(int $entityTypeId): string
	{
		return static::getMenuCode($entityTypeId);
	}

	/**
	 * @see SectionCode - use constants of this class as a first argument value
	 *
	 * @param string $sectionCode
	 * @param int $entityTypeId
	 *
	 * @return string
	 * @throws ArgumentException
	 */
	public static function getRestPlacementCode(string $sectionCode, int $entityTypeId): string
	{
		if (($entityTypeId === \CCrmOwnerType::Deal) && ($sectionCode === SectionCode::TUNNELS))
		{
			return 'CRM_FUNNELS_TOOLBAR';
		}

		$templates = static::getRestPlacementCodeTemplates();

		$template = $templates[$sectionCode] ?? null;
		if (!is_string($template))
		{
			throw new ArgumentException('The provided section code does not exist', 'sectionCode');
		}

		return str_replace(
			'#ENTITY_TYPE_NAME#',
			mb_strtoupper(\CCrmOwnerType::ResolveName($entityTypeId)),
			$template
		);
	}

	protected static function getRestPlacementCodeTemplates(): array
	{
		return [
			SectionCode::SWITCHER => 'CRM_#ENTITY_TYPE_NAME#_LIST_TOOLBAR',
			SectionCode::GRID_CONTEXT_ACTIONS => 'CRM_#ENTITY_TYPE_NAME#_LIST_MENU',
			SectionCode::DETAIL => 'CRM_#ENTITY_TYPE_NAME#_DETAIL_TOOLBAR',
			SectionCode::TIMELINE => 'CRM_#ENTITY_TYPE_NAME#_ACTIVITY_TIMELINE_MENU',
			SectionCode::DOCUMENTS => 'CRM_#ENTITY_TYPE_NAME#_DOCUMENTGENERATOR_BUTTON',
			SectionCode::TUNNELS => 'CRM_#ENTITY_TYPE_NAME#_FUNNELS_TOOLBAR',
			SectionCode::AUTOMATION => 'CRM_#ENTITY_TYPE_NAME#_ROBOT_DESIGNER_TOOLBAR',
		];
	}
}
