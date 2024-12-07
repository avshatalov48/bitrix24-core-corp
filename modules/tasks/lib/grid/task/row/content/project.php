<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Grid\Task\Row\Content;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\ProjectLimit;

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
			if (!ProjectLimit::isFeatureEnabledOrTrial())
			{
				return (
					$row['ACTION']['EDIT']
					? "<div style='cursor: pointer;' class='tasks-list-tariff-lock-container' onclick='BX.UI.InfoHelper.show(`limit_tasks_projects`)'><span class='task-list-tariff-lock'></span></div>"
					: "<span></span>"
				);
			}

			return (
				$row['ACTION']['EDIT']
					? "<div class='tasks-list-project-container'><span class='tasks-list-project-add' onclick='BX.PreventDefault(); BX.Tasks.GridActions.onProjectAddClick({$row['ID']}, this)'></span></div>"
					: "<span></span>"
			);
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

		$iconClassList = [
			'tasks-grid-avatar',
		];

		$photo = (!empty($groupImage) ? '<i style="background-image: url(\'' . Uri::urnEncode($groupImage) . '\')"></i>' : '<i></i>');

		if (
			empty($groupImage)
			&& !empty($row['GROUP_AVATAR_TYPE'])
			&& Main\Loader::includeModule('socialnetwork')
		)
		{
			$iconClassList[] = 'sonet-common-workgroup-avatar';
			$iconClassList[] = '--' . htmlspecialcharsbx(\Bitrix\Socialnetwork\Helper\Workgroup::getAvatarTypeWebCssClass($row['GROUP_AVATAR_TYPE']));
		}
		else
		{
			$iconClassList[] = 'ui-icon';
			$iconClassList[] = 'ui-icon-common-user-group';
		}

		$iconClass = implode(' ', $iconClassList);

		return "<a class='{$selector}' onclick='{$onClick}' href='javascript:void(0)'>
					<span class='{$iconClass}'>{$photo}</span>
					<span class='tasks-grid-group-inner'>{$groupName}</span><span class='tasks-grid-filter-remove'></span>
				</a>";

	}
}