<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

class Planner
{
	public static function getToolbarName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PLANNER_TOOLBAR_NAME');
	}

	/**
	 * @param int $ownerId
	 * @param int $ownerTypeId
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	public static function getToolbarMenu($ownerId, $ownerTypeId)
	{
		$ownerId = (int)$ownerId;
		$ownerTypeId = (int)$ownerTypeId;

		$menu = array();

		$providerParams = array('OWNER_TYPE_ID' => $ownerTypeId, 'OWNER_ID' => $ownerId);
		/** @var Provider\Base $provider */
		foreach (\CCrmActivity::getProviders() as $provider)
		{
			foreach ($provider::getPlannerActions($providerParams) as $action)
			{
				$actionId = isset($action['ACTION_ID']) ? (string)$action['ACTION_ID'] : '';
				if (empty($actionId))
					throw new Main\ArgumentNullException('ACTION_ID');

				$action['OWNER_ID'] = $ownerId;
				$action['OWNER_TYPE_ID'] = $ownerTypeId;

				$menu[$actionId] = array(
					'id' => $action['ACTION_ID'],
					'text' => $action['NAME'],
					'title' => $action['NAME'],
					'params' => $action,
				);
			}
		}

		return $menu;
	}
	
	public static function getToolbarButton($ownerId, $ownerTypeId)
	{
		$result = array();
		$menu = static::getToolbarMenu($ownerId, $ownerTypeId);
		if ($menu)
		{
			reset($menu);
			$firstMenuElement = current($menu);
			$defaultText = $firstMenuElement['text'];
			$defaultId = $firstMenuElement['id'];
			
			$userDefaultId = static::getDefaultActionId();
			if ($userDefaultId && array_key_exists($userDefaultId, $menu))
			{
				$defaultText = $menu[$userDefaultId]['text'];
				$defaultId = $menu[$userDefaultId]['id'];
			}
			
			$result = array(
				'TYPE' => 'toolbar-activity-planner',
				'PARAMS' => array(
					'DEFAULT_ACTION_TEXT' => $defaultText,
					'DEFAULT_ACTION_ID' => $defaultId,
					'MENU' => array_values($menu)
				),
				'TEXT' => static::getToolbarName()
			);
		}

		return $result;
	}

	private static function getDefaultActionId()
	{
		$options = \CUserOptions::GetOption('crm.interface.toolbar', 'activity_planner', array());
		$id = isset($options['default_action_id']) ? (string)$options['default_action_id'] : '';
		return $id;
	}
}