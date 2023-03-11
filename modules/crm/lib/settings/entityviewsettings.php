<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Crm;
use Bitrix\Main\Type\Date;

class EntityViewSettings
{
	const UNDEFINED          = BX_CRM_VIEW_UNDEFINED;

	const LIST_VIEW          = BX_CRM_VIEW_LIST;
	const WIDGET_VIEW        = BX_CRM_VIEW_WIDGET;
	const KANBAN_VIEW        = BX_CRM_VIEW_KANBAN;
	const ACTIVITY_VIEW      = BX_CRM_VIEW_ACTIVITY;
	const CALENDAR_VIEW      = BX_CRM_VIEW_CALENDAR;
	const DEADLINES_VIEW      = BX_CRM_VIEW_DEADLINES;

	const LIST_VIEW_NAME     = 'LIST';
	const WIDGET_VIEW_NAME   = 'WIDGET';
	const KANBAN_VIEW_NAME   = 'KANBAN';
	const ACTIVITY_VIEW_NAME   = 'ACTIVITY';
	const CALENDAR_VIEW_NAME = 'CALENDAR';
	const DEADLINES_VIEW_NAME = 'DEADLINES';

	/**
	 * Resolve view ID by name.
	 * @param string $name View Name.
	 * @return int
	 */
	public static function resolveID($name)
	{
		if($name === self::LIST_VIEW_NAME)
		{
			return self::LIST_VIEW;
		}

		if($name === self::WIDGET_VIEW_NAME)
		{
			return self::WIDGET_VIEW;
		}

		if($name === self::KANBAN_VIEW_NAME)
		{
			return self::KANBAN_VIEW;
		}

		if($name === self::ACTIVITY_VIEW_NAME)
		{
			return self::ACTIVITY_VIEW;
		}

		if($name === self::CALENDAR_VIEW_NAME)
		{
			return self::CALENDAR_VIEW;
		}

		if($name === self::DEADLINES_VIEW_NAME)
		{
			return self::DEADLINES_VIEW;
		}

		return self::UNDEFINED;
	}

	/**
	 * Resolve view name by ID.
	 * @param int $ID View ID.
	 * @return string
	 */
	public static function resolveName($ID)
	{
		if($ID === self::LIST_VIEW)
		{
			return self::LIST_VIEW_NAME;
		}

		if($ID === self::WIDGET_VIEW)
		{
			return self::WIDGET_VIEW_NAME;
		}

		if($ID === self::KANBAN_VIEW)
		{
			return self::KANBAN_VIEW_NAME;
		}

		if($ID === self::ACTIVITY_VIEW)
		{
			return self::ACTIVITY_VIEW_NAME;
		}

		if($ID === self::CALENDAR_VIEW)
		{
			return self::CALENDAR_VIEW_NAME;
		}

		if ($ID === self::DEADLINES_VIEW)
		{
			return self::DEADLINES_VIEW_NAME;
		}

		return '';
	}
	/**
	 * Get raw configuration
	 * @return array|null
	 */
	protected function getConfig()
	{
		$result = \CUserOptions::GetOption('crm.navigation', 'index', null);
		return is_array($result) ? $result : array();
	}

	/**
	 * Get current view ID by entity type ID.
	 * @param int $entityTypeID Entity Type ID.
	 * @return int
	 */
	public function getViewID($entityTypeID)
	{
		/** @var string $entityTypeName */
		$entityTypeName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeID));
		if($entityTypeName === '')
		{
			return self::UNDEFINED;
		}

		$index = $this->getConfig();
		if(!isset($index[$entityTypeName]))
		{
			return self::UNDEFINED;
		}

		$value = $index[$entityTypeName];
		$parts = explode(':', $value);
		return self::resolveID(
			mb_strtoupper(is_array($parts) && count($parts) >= 2? $parts[0] : $value)
		);
	}

	/**
	 * Get default entity type name for user interface.
	 * @return string
	 */
	public static function getDefaultPageUrl()
	{
		$settings = \CUserOptions::GetOption('ui', 'crm_control_panel_menu');
		if(is_array($settings) && isset($settings['firstPageLink']) && $settings['firstPageLink'] !== '')
		{
			if(preg_match('/\/crm\/([a-z]+)\//i', $settings['firstPageLink'], $matches) === 1
				&& count($matches) > 1
			)
			{
				$entityTypeName = $matches[1];
				$entityTypeID = \CCrmOwnerType::ResolveID($entityTypeName);
				if($entityTypeID === \CCrmOwnerType::Lead
					|| $entityTypeID === \CCrmOwnerType::Contact
					|| $entityTypeID === \CCrmOwnerType::Company
					|| $entityTypeID === \CCrmOwnerType::Deal
					|| $entityTypeID === \CCrmOwnerType::Quote
					|| $entityTypeID === \CCrmOwnerType::Invoice
				)
				{
					return "/crm/{$entityTypeName}/?redirect_to";
				}
			}

			return $settings['firstPageLink'];
		}

		$entityTypeName = mb_strtolower(Crm\Settings\LeadSettings::isEnabled()? \CCrmOwnerType::LeadName : \CCrmOwnerType::DealName);
		return "/crm/{$entityTypeName}/?redirect_to";
	}
}
