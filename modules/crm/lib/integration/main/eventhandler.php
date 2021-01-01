<?php
namespace Bitrix\Crm\Integration\Main;

use Bitrix\Main\Event;

use Bitrix\Crm\WebForm;

/**
 * Class EventHandler
 * @package Bitrix\Crm\Integration\Main
 */
class EventHandler
{
	/**
	 * Handler of event `main/onAfterSetEnumValues`.
	 *
	 * @param Event $event Event.
	 * @return void
	 */
	public static function onAfterSetEnumValues(Event $event)
	{
		$fieldId = $event->getParameter(0);
		$items = $event->getParameter(1);
		$field = \CUserTypeEntity::GetByID($fieldId);
		if(!is_array($field))
		{
			return;
		}

		if (substr($field['ENTITY_ID'], 0, 4) !== 'CRM_')
		{
			return;
		}

		WebForm\EntityFieldProvider::onUpdateUserFieldItems($field, $items);
	}

	/**
	 * Handler of event `main/OnAfterUserTypeDelete`.
	 *
	 * @param array $field Field.
	 * @param int $id ID.
	 * @return void
	 */
	public static function onAfterUserTypeDelete(array $field, $id)
	{

	}
}