<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Task\Row\Content;

/**
 * Class Project
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Project extends Content
{
	/**
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$groupId = (int)$row['GROUP_ID'];
		if (!$groupId)
		{
			return "<div></div>";
		}

		$groupName = htmlspecialcharsbx($row['GROUP_NAME']);
		$encodedData = Json::encode([
			'GROUP_ID' => [$groupId],
			'GROUP_ID_label' => [$groupName],
		]);

		$groupImage = '';
		if ($row['GROUP_IMAGE_ID'] > 0)
		{
			$arFile = \CFile::GetFileArray($row['GROUP_IMAGE_ID']);
			if (is_array($arFile))
			{
				$groupImage = $arFile['SRC'];
			}
		}

		$selected = 0;
		$selector = 'tasks-grid-group';
		if (
			isset($parameters['FILTER_FIELDS']['GROUP_ID'])
			&& is_array($parameters['FILTER_FIELDS']['GROUP_ID'])
			&& count($parameters['FILTER_FIELDS']['GROUP_ID']) === 1
			&& (int)$parameters['FILTER_FIELDS']['GROUP_ID'][0] === $groupId
		)
		{
			$selected = 1;
			$selector .= ' tasks-grid-filter-active';
		}

		$onClick = "BX.PreventDefault(); BX.Tasks.GridActions.toggleFilter({$encodedData}, {$selected})";
		if ($parameters['GROUP_ID'] > 0)
		{
			$onClick = '';
		}

		return "<a class='{$selector}' onclick='{$onClick}' href='javascript:void(0)'>
					<span class='ui-icon ui-icon-common-user-group tasks-grid-avatar'><i ". ((!empty($groupImage)) ? "style='background-image: url({$groupImage});'" : "") ."></i></span>
					<span class='tasks-grid-group-inner'>{$groupName}</span><span class='tasks-grid-filter-remove'></span>
				</a>";

	}
}