<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Intranet\Integration\Main\UISelector;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class Handler
{
	const ENTITY_TYPE_DEPARTMENTS = 'DEPARTMENTS';

	public static function OnUISelectorGetProviderByEntityType(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'intranet');

		$entityType = $event->getParameter('entityType');

		$provider = false;

		if ($entityType == self::ENTITY_TYPE_DEPARTMENTS)
		{
			$provider = new \Bitrix\Intranet\Integration\Main\UISelector\Departments;
		}

		if ($provider)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				array(
					'result' => $provider
				),
				'intranet'
			);
		}

		return $result;
	}

	public static function OnUISelectorActionProcessAjax(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'intranet');

		$action = $event->getParameter('action');

		$resultParams = false;

		if (
			$action == \Bitrix\Main\UI\Selector\Actions::GET_TREE_ITEM_DATA
			&& \Bitrix\Intranet\Util::isIntranetUser()
		)
		{
			$requestFields = $event->getParameter('requestFields');
			if (
				!empty($requestFields['options'])
				&& !empty($requestFields['options']['entityType'])
				&& $requestFields['options']['entityType'] == self::ENTITY_TYPE_DEPARTMENTS
				&& !empty($requestFields['options']['categoryId'])
				&& (
					intval($requestFields['options']['categoryId']) > 0
					|| mb_strtoupper($requestFields['options']['categoryId']) == 'EX'
				)
				&& Loader::includeModule('socialnetwork')
			)
			{
				$resultParams = \Bitrix\Socialnetwork\Integration\Main\UISelector\Entities::getDepartmentData([
					'DEPARTMENT_ID' => $requestFields['options']['categoryId'],
					'allowSearchSelf' => (isset($requestFields['options']['allowSearchSelf']) && $requestFields['options']['allowSearchSelf'] === 'N' ? 'N' : 'Y'),
				]);
			}
		}

		if ($resultParams)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				array(
					'result' => $resultParams
				),
				'socialnetwork'
			);
		}

		return $result;
	}

}
