<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Row\Content;

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
		if (!$groupId)
		{
			return "";
		}

		$groupName = htmlspecialcharsbx($row['GROUP_NAME']);
		$encodedData = Json::encode(['GROUP_ID' => [$groupId], 'GROUP_ID_label' => [$groupName]]);

		$groupImage = '';
		if ($row['GROUP_IMAGE_ID'] > 0)
		{
			$arFile = \CFile::GetFileArray($row['GROUP_IMAGE_ID']);
			if(is_array($arFile))
			{
				$groupImage = $arFile['SRC'];
			}
		}

		$selector = 'tasks-grid-group';
		if (
			isset($parameters['FILTER_FIELDS']['GROUP_ID'])
			&& is_array($parameters['FILTER_FIELDS']['GROUP_ID'])
			&& count($parameters['FILTER_FIELDS']['GROUP_ID']) === 1
			&& (int) $parameters['FILTER_FIELDS']['GROUP_ID'][0] === $groupId
		)
		{
			$selector .= ' tasks-grid-filter-active';
		}

		return "<a class='". $selector ."' onclick='BX.PreventDefault(); BX.Tasks.GridActions.toggleFilter({$encodedData})' href='javascript:void(0)'>
					<span class='ui-icon ui-icon-common-user-group tasks-grid-avatar'><i ". ((!empty($groupImage)) ? "style='background-image: url({$groupImage});'" : "") ."></i></span>
					<span class='tasks-grid-group-inner'>{$groupName}</span><span class='tasks-grid-filter-remove'></span>
				</a>";

	}
}