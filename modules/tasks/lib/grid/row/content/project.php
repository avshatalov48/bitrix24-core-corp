<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Row\Content;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

/**
 * Class Project
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Project extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$groupId = (int)$row['GROUP_ID'];
		$group = Group::getData([$groupId])[$groupId];
		$groupName = htmlspecialcharsbx($group['NAME']);
		$encodedData = Json::encode(['GROUP_ID' => $groupId, 'GROUP_ID_label' => $groupName]);

		return "<a onclick='BX.PreventDefault(); BX.Tasks.GridActions.filter({$encodedData})' href='javascript:void(0)'>{$groupName}</a>";
	}
}